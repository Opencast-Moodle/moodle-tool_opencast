<?php 
namespace Tests\DataProvider;

class SetupDataProvider {
    
    public static function getConfig($version = ''): array
    {
        $url = 'https://develop.opencast.org';
        $username = 'admin';
        $password = 'opencast';
        $timeout = 600;
        $connectTimeout = 600;
        $config =  [
            'url' => $url,
            'username' => $username,
            'password' => $password,
            'timeout' => $timeout,
            'version' => '1.9.0',
            'connect_timeout' => $connectTimeout
        ];
        if (!empty($version)) {
            $config['version'] = $version;
        }
        return $config;
    }

    public static function getMockResponses($fileName): array
    {
        $mockResponse = [];
        $mockResponsesDir = __DIR__ . "/mock_responses";
        $fileFullName = basename($fileName, ".json") . '.json';
        $filePath = $mockResponsesDir . "/" . $fileFullName;
        if (file_exists($filePath)) {
            $responseStr = file_get_contents($filePath);
            $mockResponse = json_decode($responseStr, true);
        }
        return $mockResponse !== false ? $mockResponse : [];
    }
}
?>