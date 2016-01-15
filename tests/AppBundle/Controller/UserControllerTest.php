<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Http\Headers;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testAddUser()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'POST',
            '/v1/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{"name":"Functional Test"}'
        );
        $response = $client->getResponse();


        $this->assertEquals(201, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        // @todo after every JSON request
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Missing "Content-Type: application/json" header'
        );

        // @todo check that after every request
        $this->assertTrue(
            $response->headers->contains('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With'),
            'Missing "Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With" header'
        );
        $this->assertTrue(
            $response->headers->contains('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS'),
            'Missing "Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS" header'
        );
        $this->assertTrue(
            $response->headers->contains('Access-Control-Allow-Origin', '*'),
            'Missing "Access-Control-Allow-Origin: *" header'
        );
        $this->assertTrue(
            $response->headers->contains('Access-Control-Expose-Headers', 'Location, Api-Key'),
            'Missing "Access-Control-Expose-Headers: Location, Api-Key" header'
        );

        $locationHeader = $response->headers->get('Location');
        $this->assertStringMatchesFormat('http://localhost/v1/users/%d', $locationHeader);
        $apiKey = $response->headers->get(Headers::API_KEY);
        // @todo better way to validate JWT
        $this->assertStringMatchesFormat('%s.%s.%s', $apiKey);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(3, $doctrineDataCollector->getQueryCount());

//        var_dump(
//            $profile->getCollector('db')->getQueryCount(),
//            $profile->getCollector('time')->getDuration(),
//            $profile->getCollector('memory')->getMemory()
//        );
        /* Collectors
            [0] => config
            [1] => request
            [2] => ajax
            [3] => exception
            [4] => events
            [5] => logger
            [6] => time
            [7] => memory
            [8] => router
            [9] => form
            [10] => twig
            [11] => security
            [12] => swiftmailer
            [13] => db
            [14] => dump
        */
        preg_match('#/(\d+)$#', $locationHeader, $match);

        return ['id' => $match[1], 'apiKey' => $apiKey];
    }

    public function testAddUserMissingNameError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'POST',
            '/v1/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertEquals('Request parameter "name" is empty', $jsonResponse['message'], $response->getContent());
    }

    public function testAddUserInvalidNameError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'POST',
            '/v1/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{"name":"   "}'
        );

        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringStartsWith('Request parameter name value \'   \' violated a constraint', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testGetUser(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'GET',
            '/v1/users/' . $userData['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(200, $response->getStatusCode(), $response);
        $this->assertEquals(['name' => 'Functional Test'], $jsonResponse, $response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(1, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testGetUserIncorrectApiKeyError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'GET',
            '/v1/users/' . $userData['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer wrong']
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(401, $response->getStatusCode(), $response);
        $this->assertEquals(210, $jsonResponse['code'], $response->getContent());
        $this->assertEquals('API key `wrong` is invalid', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testGetUserIncorrectUserIdError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'GET',
            '/v1/users/' . ($userData['id'] - 1),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }
}
