<?php
namespace OpencastApi\Mock;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class OcMockHanlder
{
    /**
     * Returns a closure function that handles the mock request by matching against a given data list
     * More information about this method can be found in README.md file.
     *
     * @param array $data the formatted response data list
     * @param string $recordFilePath the path to record all incoming requests, which can be used to find out what are the actaul requests.
     *
     * @return Closure $customHandler the custom handler
     */
    public static function getHandlerStackWithPath($data, $recordFilePath = null)
    {
        $customHandler = function (Request $request) use ($data, $recordFilePath) {
            $path = $request->getUri()->getPath();
            $host = $request->getUri()->getHost();
            $query = $request->getUri()->getQuery();
            $method = $request->getMethod();
            $requestBody = $request->getBody()->getContents();

            $fullPath = $path;
            if (!empty($query)) {
                $fullPath .= '?' . urldecode($query);
            }

            if (!empty($recordFilePath) && file_exists($recordFilePath) && is_writable($recordFilePath)) {
                $recordMessage = '[' . date("d.m.Y H:i:s") . ']: ' . "($method) " . urldecode($request->getUri()->__toString()) . PHP_EOL;
                file_put_contents($recordFilePath, $recordMessage, FILE_APPEND);
            }

            $status = 404;
            $headers = [];
            $body = '';
            $version = '1.1';
            $reason = null;
            $reasonData = [
                'content' => 'Not Found',
                'full_path' => $fullPath,
                'method' => $method,
                'requestBody' => $requestBody,
            ];
            $response = new Response($status, $headers, $body, $version, json_encode($reasonData));

            if ($method === 'PUT' && !empty($requestBody)) {
                $requestBody = urldecode($requestBody);
            }
            foreach ($data as $resPath => $resData) {
                if (self::checkPath($resPath, $fullPath) && isset($resData[$method])) {
                    $resObj = null;
                    if (in_array($method, ['POST', 'PUT']) && is_array($resData[$method]) && count($resData[$method]) > 1) {
                        $filter = array_filter($resData[$method], function ($res) use ($requestBody) {
                            return !empty($res['params']['unique_request_identifier']) &&
                                strpos($requestBody, $res['params']['unique_request_identifier']) !== false;
                        });
                        if (!empty($filter)) {
                            $resObj = reset($filter);
                        }
                    } else {
                        $resObj = reset($resData[$method]);
                    }
                    if (!empty($resObj)) {
                        $status = $resObj['status'] ?? $status;
                        $headers = $resObj['headers'] ?? $headers;
                        $body = $resObj['body'] ?? $body;
                        $version = $resObj['version'] ?? $version;
                        $reason = $resObj['reason'] ?? $reason;
                        $response = new Response($status, $headers, $body, $version, $reason);
                    }
                }
            }
            return $response;
        };
        return $customHandler;
    }

    /**
     * Checks if response path and request path are matched.
     *
     * @param string $responsePath the response path
     * @param string $requestPath the request path
     *
     * @return bool true if matches, false otherwise
     */
    private static function checkPath($responsePath, $requestPath)
    {
        $responsePath = urldecode($responsePath);
        $$requestPath = urldecode($requestPath);

        $resParsedUrl = parse_url($responsePath);
        $resPath = $resParsedUrl['path'];
        $resQueryArray = [];
        if (!empty($resParsedUrl['query'])) {
            parse_str($resParsedUrl['query'], $resQueryArray);
        }

        $reqParsedUrl = parse_url($requestPath);
        $reqPath = $reqParsedUrl['path'];
        $reqQueryArray = [];
        if (!empty($reqParsedUrl['query'])) {
            parse_str($reqParsedUrl['query'], $reqQueryArray);
        }

        if (!empty($resQueryArray)) {
            ksort($resQueryArray);
        }
        if (!empty($reqQueryArray)) {
            ksort($reqQueryArray);
        }

        return $resPath === $reqPath && json_encode($resQueryArray) === json_encode($reqQueryArray);
    }
}
?>