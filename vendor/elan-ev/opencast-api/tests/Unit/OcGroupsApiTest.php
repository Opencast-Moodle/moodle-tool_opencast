<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcGroupsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);
        $this->ocGroupsApi = $ocRestApi->groupsApi;
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\GroupsDataProvider::getAllCases()
     */
    public function get_all_groups($sort, $limit, $offset, $filter): void
    {
        $response = $this->ocGroupsApi->getAll($sort, $limit, $offset, $filter);
        $this->assertSame(200, $response['code'], 'Failure to get groups list');
    }

    /**
     * @test
     */
    public function empty_created_id(): string
    {
        $createdgroupIdentifier = '';
        $this->assertEmpty($createdgroupIdentifier);

        return $createdgroupIdentifier;
    }

    /**
     * @test
     * @depends empty_created_id
     */
    public function create_get_update_delete_group(string $identifier): string
    {
        $name = 'PHPUNIT_TESTING_GROUP_' . uniqid();
        // Get the group.
        $response0 = $this->ocGroupsApi->get(strtolower($name));
        if ($response0['code'] == 404) {
            // Create
            $response1 = $this->ocGroupsApi->create($name);
            $this->assertSame(201, $response1['code'], 'Failure to create a group');
        }

        // Get the group again!
        $response2 = $this->ocGroupsApi->get(strtolower($name));
        $this->assertSame(200, $response2['code'], 'Failure to get group');
        $group = $response2['body'];
        $this->assertNotEmpty($group);
        $identifier = $group->identifier;

        // Update the group
        $response3 = $this->ocGroupsApi->update($group->identifier, $name, 'THIS IS AN UPDATED DESC FROM PHPUNIT');
        $this->assertSame(200, $response3['code'], 'Failure to update group');

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends create_get_update_delete_group
     */
    public function add_delete_member_in_group(string $identifier): string
    {
        // Add member.
        $member = 'opencast_capture_agent';
        $response1 = $this->ocGroupsApi->addMember($identifier, $member);
        $this->assertSame(200, $response1['code'], 'Failure to add member to a group');

        // Delete member from group.
        $response2 = $this->ocGroupsApi->deleteMember($identifier, $member);
        $this->assertSame(200, $response2['code'], 'Failure to delete member from a group');

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends add_delete_member_in_group
     */
    public function delete_group(string $identifier): void
    {
        $response = $this->ocGroupsApi->delete($identifier);
        $this->assertContains($response['code'], [200, 204], 'Failure to delete group');
    }
}
?>
