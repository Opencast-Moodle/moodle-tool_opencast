<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace Tests\DataProvider;

class SetupDataProvider {
    public static function getConfig($version = ''): array {
        $url = 'https://stable.opencast.org';
        $username = 'admin';
        $password = 'opencast';
        $timeout = 0;
        $connectTimeout = 0;
        $config = [
            'url' => $url,
            'username' => $username,
            'password' => $password,
            'timeout' => $timeout,
            'version' => '1.11.0',
            'connect_timeout' => $connectTimeout,
            'features' => [
                'lucene' => false,
            ],
        ];
        if (!empty($version)) {
            $config['version'] = $version;
        }
        return $config;
    }

    public static function getMockResponses($data): array {
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
