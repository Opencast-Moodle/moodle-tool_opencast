<?php
namespace Tests\DataProvider;

class SetupDataProvider {

    public static function getConfig($version = ''): array
    {
        $url = 'https://stable.opencast.org';
        $username = 'admin';
        $password = 'opencast';
        $timeout = 0;
        $connectTimeout = 0;
        $config =  [
            'url' => $url,
            'username' => $username,
            'password' => $password,
            'timeout' => $timeout,
            'version' => '1.11.0',
            'connect_timeout' => $connectTimeout,
            'features' => [
                'lucene' => false
            ]
        ];
        if (!empty($version)) {
            $config['version'] = $version;
        }
        return $config;
    }

    public static function getMockResponses($data): array
    {
        $responseNames = [];
        if (!is_array($data)) {
            $responseNames[] = $data;
        } else {
            $responseNames = $data;
        }
        $mockResponse = [];
        $mockResponsesDir = __DIR__ . "/mock_responses";
        foreach ($responseNames as $fileName) {
            $fileFullName = basename($fileName, ".json") . '.json';
            $filePath = $mockResponsesDir . "/" . $fileFullName;
            if (file_exists($filePath)) {
                $responseStr = file_get_contents($filePath);
                $mockResponse = array_merge($mockResponse, json_decode($responseStr, true));
            }
        }
        return $mockResponse !== false ? $mockResponse : [];
    }
}
?>
