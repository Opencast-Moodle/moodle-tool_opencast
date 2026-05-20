<?php
namespace OpencastApi\Rest;

use GuzzleHttp\Client;
use OpencastApi\Auth\JWT\OcJwtClaim;
use OpencastApi\Auth\JWT\OcJwtHandler;
use GuzzleHttp\Exception\RequestException;

class OcRestClient extends Client
{
    private string $baseUri;
    private string $username;
    private string $password;
    private int $timeout = 0;
    private int $connectTimeout = 0;
    private ?int $disposableTimeout = null;
    private ?int $disposableConnectTimeout = null;
    private ?string $version = null;
    private array $headerExceptions = [];
    private array $additionalHeaders = [];
    private bool $noHeader = false;
    private ?array $origin = null;
    private array $features = [];
    private array $globalOptions = [];
    private array $jwtConfig = [];
    private ?OcJwtClaim $jwtClaim = null;
    private ?OcJwtHandler $jwtHandler = null;

    /*
        $config = [
            'url' => 'https://develop.opencast.org/',       // The API url of the opencast instance (required)
            'username' => 'admin',                          // The API username. (required)
            'password' => 'opencast',                       // The API password. (required)
            'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
            'connect_timeout' => 0,                         // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
            'version' => null,                               // The API Version. (Default null). (optional)
            'handler' => null,                               // The callable Handler or HandlerStack. (Default null). (optional)
            'features' => null,                              // A set of additional features [e.g. lucene search]. (Default null). (optional)
            'guzzle' => null,                                // Additional Guzzle Request Options. These options can overwrite some default options (Default null). (optional)
            'jwt' => [                                      // JWT Configuration, null will deactivate the guard
                'private_key' => 'your-private-key-content',    // Private Key string.
                'algorithm' => 'ES256',                         // Selected algorithm. @see OpencastApi\Auth\JWT\OcJwtHandler::SUPPORTED_ALGORITHMS
                'expiration' => 15                              // Expiration time in seconds. default to 15 seconds.
            ],
        ]
    */
    public function __construct($config)
    {
        $this->baseUri = $config['url'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
        if (isset($config['connect_timeout'])) {
            $this->connectTimeout = $config['connect_timeout'];
        }

        if (isset($config['version'])) {
            $this->setVersion($config['version']);
        }

        $parentConstructorConfig = [
            'base_uri' => $this->baseUri
        ];

        if (isset($config['handler']) && is_callable($config['handler'])) {
            $parentConstructorConfig['handler'] = $config['handler'];
        }

        if (isset($config['features'])) {
            $this->features = $config['features'];
        }

        if (isset($config['guzzle'])) {
            $this->globalOptions = $config['guzzle'];
        }

        if (isset($config['jwt']) && is_array($config['jwt'])) {
            $this->jwtConfig = $config['jwt'];
            $this->jwtHandler = new OcJwtHandler(
                $this->jwtConfig['private_key'],
                $this->jwtConfig['algorithm'] ?? null,
                $this->jwtConfig['expiration'] ?? null
            );
        }

        parent::__construct($parentConstructorConfig);
    }

    public function getJwtHandler(): ?OcJwtHandler
    {
        return $this->jwtHandler;
    }

    public function readFeatures($key = null) {
        if (empty($key)) {
            return $this->features;
        }

        if (isset($this->features[$key])) {
            return $this->features[$key];
        }
        return false;
    }

    public function registerHeaderException($header, $path) {
        $path = ltrim($path, '/');
        if (!isset($this->headerExceptions[$header]) || !in_array($path, $this->headerExceptions[$header])) {
            $this->headerExceptions[$header][] = $path;
        }
    }

    public function setJwtClaims(OcJwtClaim $jwtClaim)
    {
        $this->jwtClaim = $jwtClaim;
    }

    public function registerAdditionalHeader($header, $value)
    {
        $this->additionalHeaders[$header] = $value;
    }

    public function enableNoHeader()
    {
        $this->noHeader = true;
    }

    public function setRequestTimeout($timeout)
    {
        $this->disposableTimeout = $timeout;
    }

    public function setRequestConnectionTimeout($connectionTimeout)
    {
        $this->disposableConnectTimeout = $connectionTimeout;
    }

    private function addRequestOptions($uri, $options)
    {
        $globalOptions = $this->globalOptions;

        // Perform a temp no header request.
        if ($this->noHeader) {
            $this->noHeader = false;
            return array_merge($globalOptions, $options, ['headers' => null]);
        }

        $generalOptions = [];
        // Auth
        if (!empty($this->username) && !empty($this->password)) {
            $generalOptions['auth'] = [$this->username, $this->password];
        }

        // Timeout + disposable
        if (isset($this->timeout)) {
            $generalOptions['timeout'] = $this->timeout;
        }

        if (!is_null($this->disposableTimeout)) {
            $generalOptions['timeout'] = $this->disposableTimeout;
            $this->disposableTimeout = null;
        }

        // Connect Timeout + disposable
        if (isset($this->connectTimeout)) {
            $generalOptions['connect_timeout'] = $this->connectTimeout;
        }

        if (!is_null($this->disposableConnectTimeout)) {
            $generalOptions['connect_timeout'] = $this->disposableConnectTimeout;
            $this->disposableConnectTimeout = null;
        }

        // Opencast API Version.
        if (!empty($this->version)) {
            $this->registerAdditionalHeader('Accept', "application/v{$this->version}+json");
        }

        if (!empty($this->additionalHeaders)) {
            $generalOptions['headers'] = $this->additionalHeaders;
            $this->additionalHeaders = [];
            foreach ($generalOptions['headers'] as $header => $value) {
                $path = explode('/', ltrim($uri, '/'))[0];
                if (isset($this->headerExceptions[$header]) && in_array($path, $this->headerExceptions[$header]) ) {
                    unset($generalOptions['headers'][$header]);
                }
            }
        }

        $requestOptions = array_merge($generalOptions, $globalOptions, $options);
        return $requestOptions;
    }

    /**
     * Ensures JWT authentication for the request by injecting a JWT token into the request options.
     *
     * If JWT configuration and claims are set, this method generates a JWT token and adds it to the request
     * according to the HTTP method (GET, POST, PUT, etc.). It also removes basic auth credentials if JWT is used.
     *
     * @param array $requestOptions The original request options.
     * @param string $method The HTTP method (e.g., GET, POST, PUT).
     * @return array The modified request options with JWT authentication applied.
     */
    private function ensureJwtAuthGuard(array $requestOptions, string $method): array
    {
        if (isset($this->jwtClaim) && !empty($this->jwtConfig)) {
            $privateKeyString = $this->jwtConfig['private_key'];
            $algorithmKey = $this->jwtConfig['algorithm'] ?? null;
            $expDuration = $this->jwtConfig['expiration'] ?? null;

            $jwtHandler = new OcJwtHandler($privateKeyString, $algorithmKey, $expDuration);
            $jwtToken = $jwtHandler->issueToken($this->jwtClaim);

            switch ($method) {
                case 'GET':
                    $requestOptions['query']['jwt'] = (string) $jwtToken;
                    break;
                case 'PUT':
                case 'POST':
                    if (isset($requestOptions['form_params'])) {
                        $requestOptions['form_params']['jwt'] = (string) $jwtToken;
                    } else if (isset($requestOptions['multipart'])) {
                        $requestOptions['multipart'][] = [
                            'name' => 'jwt',
                            'contents' => (string) $jwtToken
                        ];
                    }
                    break;
                default:
                    $requestOptions['headers'][] = "Authorization: Bearer " . (string) $jwtToken;
                    break;
            }

            // As we now have a JWT token, we can remove the basic auth credentials.
            if (isset($requestOptions['auth'])) {
                unset($requestOptions['auth']);
            }
        }
        return $requestOptions;
    }

    public function hasVersion($version)
    {
        if (empty($this->version)) {
            try {
                // We have to use an aux object, in order to prevent overwriting arguments of current object.
                $aux = clone $this;
                $aux->enableNoHeader();
                $options = [];
                if (!empty($this->username) && !empty($this->password)) {
                    $options['auth'] = [$this->username, $this->password];
                }
                $defaultVersion = $aux->performGet('/api/version/default', $options);
                if (!empty($defaultVersion['body']) && isset($defaultVersion['body']->default)) {
                    $this->setVersion(str_replace(['application/', 'v', '+json'], ['', '', ''], $defaultVersion['body']->default));
                } else {
                    return false;
                }
            } catch (\Throwable $th) {
                return false;
            }
        }
        return version_compare($this->version, $version, '>=');

    }

    private function setVersion($version)
    {
        $version = str_replace(['application/', 'v', '+json'], ['', '', ''], $version);
        $this->version = $version;
    }

    public function getVersion() {
        return $this->version;
    }

    private function resolveResponseBody($response)
    {
        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents() ?? '';
        $result = json_decode($contents);
        if ($result !== null) {
            return $result;
        }
        if (!empty($contents)) {
            return $contents;
        }

        return null;
    }

    private function returnResult($response)
    {
        $result = [];
        $result['code'] = $response->getStatusCode();
        $result['reason'] = $response->getReasonPhrase();
        $result['body'] = $this->resolveResponseBody($response);

        $location = '';
        if ($response->hasHeader('Location')) {
            $location = $response->getHeader('Location');
        }
        $result['location'] = $location;

        $result['origin'] = !empty($this->origin) ? $this->origin : null;

        return $result;
    }

    public function performGet($uri, $options = [])
    {
        $this->prepareOrigin($uri, $options, 'GET');
        try {
            $requestOptions = $this->addRequestOptions($uri, $options);
            $requestOptions = $this->ensureJwtAuthGuard($requestOptions, 'GET');
            $response = $this->get($uri, $requestOptions);
            return $this->returnResult($response);
        } catch (\Throwable $th) {
            return $this->resolveException($th);
        }
    }

    public function performPost($uri, $options = [])
    {
        $this->prepareOrigin($uri, $options, 'POST');
        try {
            $requestOptions = $this->addRequestOptions($uri, $options);
            $requestOptions = $this->ensureJwtAuthGuard($requestOptions, 'POST');
            $response = $this->post($uri, $requestOptions);
            return $this->returnResult($response);
        } catch (\Throwable $th) {
            return $this->resolveException($th);
        }
    }


    public function performPut($uri, $options = [])
    {
        $this->prepareOrigin($uri, $options, 'PUT');
        try {
            $requestOptions = $this->addRequestOptions($uri, $options);
            $requestOptions = $this->ensureJwtAuthGuard($requestOptions, 'PUT');
            $response = $this->put($uri, $requestOptions);
            return $this->returnResult($response);
        } catch (\Throwable $th) {
            return $this->resolveException($th);
        }
    }

    public function performDelete($uri, $options = [])
    {
        $this->prepareOrigin($uri, $options, 'DELETE');
        try {
            $requestOptions = $this->addRequestOptions($uri, $options);
            $requestOptions = $this->ensureJwtAuthGuard($requestOptions, 'DELETE');
            $response = $this->delete($uri, $requestOptions);
            return $this->returnResult($response);
        } catch (\Throwable $th) {
            return $this->resolveException($th);
        }
    }

    private function resolveException(\Throwable $th)
    {
        $error = [];
        $error['code'] = $th->getCode();
        $error['reason'] = $th->getMessage();

        $bodyContents = '';
        $location = '';
        if ($th instanceof RequestException && $th->hasResponse()) {
            $response = $th->getResponse();
            $bodyContents = $this->resolveResponseBody($response);
            if ($response->hasHeader('Location')) {
                $location = $response->getHeader('Location');
            }
        }
        $error['body'] = $bodyContents;
        $error['location'] = $location;
        $error['origin'] = !empty($this->origin) ? $this->origin : null;
        if (!empty($error['reason'])) {
            return $error;
        }

        $reason = 'Unable to perform the request!';
        if ($th instanceof \GuzzleHttp\Exception\ConnectException) {
            $reason = 'Connection Error';
        } else if ($th instanceof \GuzzleHttp\Exception\ServerException) {
            $reason = 'Internal Server Error';
        } else if ($th instanceof \GuzzleHttp\Exception\ClientException) {
            $reason = 'Client Error';
        } else if ($th instanceof \GuzzleHttp\Exception\TooManyRedirectsException) {
            $reason = 'Too Many Redirect Error';
        }
        $error['reason'] = $reason;

        return $error;
    }

    public function getFormParams($params)
    {
        $options = [];
        $formParams = [];
        foreach ($params as $field_name => $field_value) {
            $formParams[$field_name] = (!is_string($field_value)) ? json_encode($field_value) : $field_value;
        }
        if (!empty($formParams)) {
            $options['form_params'] = $formParams;
        }
        return $options;
    }

    public function getMultiPartFormParams($params)
    {
        $options = [];
        $multiParams = [];
        foreach ($params as $field_name => $field_value) {
            $multiParams[] = [
                'name' => $field_name,
                'contents' => $field_value
            ];
        }
        if (!empty($multiParams)) {
            $options['multipart'] = $multiParams;
        }
        return $options;
    }

    public function getQueryParams($params)
    {
        $options = [];
        $queryParams = [];
        foreach ($params as $field_name => $field_value) {
            $value = is_bool($field_value) ? json_encode($field_value) : $field_value;
            $queryParams[$field_name] = $value;
        }
        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }
        return $options;
    }

    private function prepareOrigin($uri, $options, $method)
    {
        $this->origin = [
            'base' => $this->baseUri,
            'path' => $uri,
            'method' => $method,
            'params' => [
                'query_params' => isset($options['query']) ? $options['query'] : [],
                'form_params' => isset($options['form_params']) ? $options['form_params'] : [],
                'form_multipart_params' => isset($options['multipart']) ? $options['multipart'] : [],
                'json' => isset($options['json']) ? $options['json'] : [],
                'body' => isset($options['body']) ? $options['body'] : null,
            ]
        ];
    }
}
?>
