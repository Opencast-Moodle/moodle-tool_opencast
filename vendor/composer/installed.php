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

return [
    'root' => [
        'name' => 'moodle/tool_opencast',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'ea9f639754a137125c4006e96ae45fd8dfcd47a0',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => [],
        'dev' => true,
    ],
    'versions' => [
        'elan-ev/opencast-api' => [
            'pretty_version' => '1.9.0',
            'version' => '1.9.0.0',
            'reference' => '041f29d10f9572b038e8665f6cc2f9c86d488da8',
            'type' => 'library',
            'install_path' => __DIR__ . '/../elan-ev/opencast-api',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'guzzlehttp/guzzle' => [
            'pretty_version' => '7.9.3',
            'version' => '7.9.3.0',
            'reference' => '7b2f29fe81dc4da0ca0ea7d42107a0845946ea77',
            'type' => 'library',
            'install_path' => __DIR__ . '/../guzzlehttp/guzzle',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'guzzlehttp/promises' => [
            'pretty_version' => '2.2.0',
            'version' => '2.2.0.0',
            'reference' => '7c69f28996b0a6920945dd20b3857e499d9ca96c',
            'type' => 'library',
            'install_path' => __DIR__ . '/../guzzlehttp/promises',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'guzzlehttp/psr7' => [
            'pretty_version' => '2.7.1',
            'version' => '2.7.1.0',
            'reference' => 'c2270caaabe631b3b44c85f99e5a04bbb8060d16',
            'type' => 'library',
            'install_path' => __DIR__ . '/../guzzlehttp/psr7',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'moodle/tool_opencast' => [
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'ea9f639754a137125c4006e96ae45fd8dfcd47a0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'psr/http-client' => [
            'pretty_version' => '1.0.3',
            'version' => '1.0.3.0',
            'reference' => 'bb5906edc1c324c9a05aa0873d40117941e5fa90',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/http-client',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'psr/http-client-implementation' => [
            'dev_requirement' => false,
            'provided' => [
                0 => '1.0',
            ],
        ],
        'psr/http-factory' => [
            'pretty_version' => '1.1.0',
            'version' => '1.1.0.0',
            'reference' => '2b4765fddfe3b508ac62f829e852b1501d3f6e8a',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/http-factory',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'psr/http-factory-implementation' => [
            'dev_requirement' => false,
            'provided' => [
                0 => '1.0',
            ],
        ],
        'psr/http-message' => [
            'pretty_version' => '2.0',
            'version' => '2.0.0.0',
            'reference' => '402d35bcb92c70c026d1a6a9883f06b2ead23d71',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/http-message',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'psr/http-message-implementation' => [
            'dev_requirement' => false,
            'provided' => [
                0 => '1.0',
            ],
        ],
        'ralouphie/getallheaders' => [
            'pretty_version' => '3.0.3',
            'version' => '3.0.3.0',
            'reference' => '120b605dfeb996808c31b6477290a714d356e822',
            'type' => 'library',
            'install_path' => __DIR__ . '/../ralouphie/getallheaders',
            'aliases' => [],
            'dev_requirement' => false,
        ],
        'symfony/deprecation-contracts' => [
            'pretty_version' => 'v3.6.0',
            'version' => '3.6.0.0',
            'reference' => '63afe740e99a13ba87ec199bb07bbdee937a5b62',
            'type' => 'library',
            'install_path' => __DIR__ . '/../symfony/deprecation-contracts',
            'aliases' => [],
            'dev_requirement' => false,
        ],
    ],
];
