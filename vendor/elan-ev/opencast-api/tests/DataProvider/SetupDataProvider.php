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
            'version' => '1.6.0',
            'connect_timeout' => $connectTimeout
        ];
        if (!empty($version)) {
            $config['version'] = $version;
        }
        return $config;
    }
}
?>