<?php
namespace OpencastApi\Mock;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class OcMockHanlder
{
    public static function getHandlerStackWithPath($data)
    {
        $mockResponses = [];
        $customHandler = function (Request $request) use ($data) {
            $path = $request->getUri()->getPath();
            $query = $request->getUri()->getQuery();
            $method = $request->getMethod();
            $requestBody = $request->getBody()->getContents();

            $fullPath = $path;
            if (!empty($query)) {
                $fullPath .= '?' . urldecode($query);
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
            // if (!empty($requestBody)) {
            //     if ($method == 'POST') {
            //         $iswritten = file_put_contents('/Users/farbod/Workspace/AVVP/oc-php-lib-post-params.txt', $requestBody);
            //     }
            //     if ($method == 'PUT') {
            //         $iswritten = file_put_contents('/Users/farbod/Workspace/AVVP/oc-php-lib-put-params.txt', $requestBody);
            //     }
            // }
            foreach ($data as $resPath => $resData) {
                if ($resPath === $fullPath && isset($resData[$method])) {
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
}
?>