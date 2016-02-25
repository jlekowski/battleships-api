<?php

namespace Tests\AppBundle\Security;

use AppBundle\Security\ApiKeyManager;

class ApiKeyManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApiKeyManager
     */
    protected $apiKeyManager;

    public function setUp()
    {
        $this->apiKeyManager = new ApiKeyManager('secret');
    }

    public function testGenerateApiKeyForUserAndGetInfoFromApiKey()
    {
        $user = $this->prophesize('AppBundle\Entity\User');

        $user->getId()->willReturn(1);
        $user->getToken()->willReturn('token');


        $apiKey = $this->apiKeyManager->generateApiKeyForUser($user->reveal());
        $this->assertStringMatchesFormat('%s.%s.%s', $apiKey);

        $apiKeyInfo = $this->apiKeyManager->getInfoFromApiKey($apiKey);
        $this->assertEquals(1, $apiKeyInfo->id);
        $this->assertEquals('token', $apiKeyInfo->token);
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidApiKeyException
     * @expectedExceptionMessage API key `apiKey` is invalid
     */
    public function testGetInfoFromApiKeyThrowsExceptionForInvalidApiKey()
    {
        $this->apiKeyManager->getInfoFromApiKey('apiKey');
    }
}
