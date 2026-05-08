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

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use OpencastApi\Mock\OcMockHanlder;

class OcPlaylistsApiTestMock extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('api_playlists');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for playlists api could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], false);
        $this->ocPlaylistsApi = $ocRestApi->playlistsApi;
    }

    /**
     * @test
     */
    public function get_all_playlists(): void {
        $response = $this->ocPlaylistsApi->getAll();
        $this->assertSame(200, $response['code'], 'Failure to get playlists list');
    }

    /**
     * @test
     */
    public function empty_created_id(): string {
        $createdSeriesIdentifier = '';
        $this->assertEmpty($createdSeriesIdentifier);

        return $createdSeriesIdentifier;
    }

    /**
     * @test
     * @depends empty_created_id
     */
    public function create_get_playlist(string $identifier): string {
        // Create Playlist.
        $response1 = $this->ocPlaylistsApi->create(
            \Tests\DataProvider\PlaylistsDataProvider::getPlaylist()
        );
        $this->assertSame(201, $response1['code'], 'Failure to create a playlist');
        $playlist = $response1['body'];
        $this->assertNotEmpty($playlist);

        $identifier = $playlist->id;

        // Get the playlist.
        $response2 = $this->ocPlaylistsApi->get($identifier);
        $this->assertSame(200, $response2['code'], 'Failure to get a playlist');
        $playlist = $response2['body'];
        $this->assertNotEmpty($playlist);

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends create_get_playlist
     */
    public function get_update_playlist(string $identifier): string {
        // Get playlist.
        $response1 = $this->ocPlaylistsApi->get($identifier);
        $this->assertSame(200, $response1['code'], 'Failure to get playlist');
        $playlist = $response1['body'];
        $this->assertNotEmpty($playlist);

        // Update playlist.
        $playlist = str_replace(
            '{update_replace}',
            'UPDATED ON: ' . strtotime('now'),
            \Tests\DataProvider\PlaylistsDataProvider::getPlaylist()
        );
        $response3 = $this->ocPlaylistsApi->update($identifier, $playlist);
        $this->assertSame(200, $response3['code'], 'Failure to update playlist');
        $playlist = $response3['body'];
        $this->assertNotEmpty($playlist);

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends get_update_playlist
     */
    public function update_delete_entries(string $identifier): string {
        // Delete all entries.
        $response1 = $this->ocPlaylistsApi->emptyEntries($identifier);
        $this->assertSame(200, $response1['code'], 'Failure to delete entries of a playlist');
        $playlist = $response1['body'];
        $this->assertNotEmpty($playlist);

        // Prepare to update entries.
        $entries = \Tests\DataProvider\PlaylistsDataProvider::getEntries();
        $response2 = $this->ocPlaylistsApi->updateEntries($identifier, $entries);
        $this->assertSame(200, $response2['code'], 'Failure to update entries of a playlist');
        $playlist = $response2['body'];
        $this->assertNotEmpty($playlist);

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends update_delete_entries
     */
    public function delete_playlist(string $identifier): void {
        $response = $this->ocPlaylistsApi->delete($identifier);
        $this->assertSame(200, $response['code'], 'Failure to delete a playlist');
        $playlist = $response['body'];
        $this->assertNotEmpty($playlist);
    }
}
