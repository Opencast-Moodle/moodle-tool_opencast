<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\OcTestCase;
use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;
use OpencastApi\Auth\JWT\OcJwtClaim;

#[\AllowDynamicProperties]
class OcJwtAuthTest extends OcTestCase
{
    private $ocInfo;
    private $jwtHandler;
    private $ocEventsApi;
    private $exOcRestApi;

    public static function setUpBeforeClass(): void
    {
        echo "\n\n====== Start Testing (" . __CLASS__ . ") ======\n";
        $config = \Tests\DataProvider\SetupDataProvider::getConfigWithJwt();
        // Since we're using a personalized Opencast instance, we print the configuration beforehand to avoid confusion.
        echo "URL: {$config['url']}\n";
        echo "Username: {$config['username']}\n";
        echo "Algorithm: {$config['jwt']['algorithm']}\n";
        echo "======\n";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfigWithJwt();

        $ocRestApi = new Opencast($config, [], false);
        $this->jwtHandler = $ocRestApi->getRestJwtHandler();
        $this->ocInfo = $ocRestApi->info;
        $this->ocEventsApi = $ocRestApi->eventsApi;
        $this->exOcRestApi = new OcRestClient($config);
    }

    /**
     * @test
     */
    public function get_info_me_with_jwt_claim_setters(): void
    {
        $ocClaim = new OcJwtClaim();
        $name = 'JWT TEST USER 1';
        $username = 'jwt_test_user_1';
        $email = 'jwt_test_user_1@test.test';
        $ocClaim->setUserInfoClaims($username, $name, $email);
        $ocClaim->setRoles(['ROLE_USER_1', 'ROLE_JWT_USER_1']);
        $response = $this->ocInfo->withClaims($ocClaim)->getInfoMeJson();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
        $body = $response['body'];
        $this->assertEquals($body->user->name, $name, 'JWT Auth failed with invalid name!');
    }

    /**
     * @test
     */
    public function get_info_me_with_jwt_claim_array_creator(): void
    {
        $userInfoArray = [
            'sub' => 'jwt_test_user_2',
            'name' => 'JWT TEST USER 2',
            'email' => 'jwt_test_user_2@test.test',
            'roles' => ['ROLE_USER_2', 'ROLE_JWT_USER_2'],
        ];
        $ocClaim = OcJwtClaim::createFromArray($userInfoArray);
        $response = $this->ocInfo->withClaims($ocClaim)->getInfoMeJson();
        $this->assertSame(200, $response['code'], 'Failure to get user roles');
        $body = $response['body'];
        $this->assertContains($userInfoArray['roles'][0], $body->roles, 'JWT Auth failed with invalid roles!');
        $this->assertContains($userInfoArray['roles'][1], $body->roles, 'JWT Auth failed with invalid roles!');
    }

    /**
     * @test
     */
    public function static_file_access_with_jwt(): void
    {
        $response1 = $this->ocEventsApi->getAll(['withpublications' => true, 'limit' => 4]);
        $body1 = $response1['body'];
        $mediaUrl = $body1[0]->publications[0]->media[0]->url;
        $eventId = $body1[0]->identifier;

        $ocClaim = new OcJwtClaim();
        $eventAcl = [
            "$eventId" => ['read']
        ];
        $ocClaim->setEventAcls($eventAcl);
        $this->exOcRestApi->setJwtClaims($ocClaim);
        $response3 = $this->exOcRestApi->performGet($mediaUrl);
        $this->assertSame(200, $response3['code'], 'Failure to access media directly with JWT.');
    }

    /**
     * @test
     */
    public function generate_validate_token(): void
    {
        $response1 = $this->ocEventsApi->getAll(['withpublications' => true, 'limit' => 4]);
        $body1 = $response1['body'];
        $attachmentUrl = $body1[0]->publications[0]->attachments[0]->url;
        $eventId = $body1[0]->identifier;

        $ocClaim = new OcJwtClaim();
        $eventAcl = [
            "$eventId" => ['read']
        ];
        $ocClaim->setEventAcls($eventAcl);
        // Issue Token.
        $accessToken = $this->exOcRestApi->getJwtHandler()->issueToken($ocClaim) ?? null;
        $this->assertNotEmpty($accessToken);

        // Attach the token to the url and get the content.
        $attachmentUrlWithJwt = $attachmentUrl . '?jwt=' . $accessToken;
        $fileContent = file_get_contents($attachmentUrlWithJwt);
        $this->assertNotEmpty($fileContent);

        // Validate the token.
        $isValid = $this->exOcRestApi->getJwtHandler()->validateToken($accessToken);
        $this->assertSame(true, $isValid, 'Invalid Token');

        // Match ACL actions.
        $oldClaim = $this->exOcRestApi->getJwtHandler()->getOcJwtClaimFromTokenString($accessToken);
        $this->assertNotEmpty($oldClaim);

        $matchedActions = $oldClaim->actionsMatchFor(OcJwtClaim::OC_EVENT, $eventId, ['read']);
        $this->assertSame(true, $matchedActions, 'Actions mismatched.');
    }
}
?>
