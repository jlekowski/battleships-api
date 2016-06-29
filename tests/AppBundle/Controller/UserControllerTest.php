<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Http\Headers;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;

class UserControllerTest extends AbstractApiTestCase
{
    public function testAddUser()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $body = ['name' => '  Functional Test Trim  '];
        $client->request(
            'POST',
            '/v1/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($body)
        );
        $response = $client->getResponse();


        $this->assertEquals(201, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        $this->assertJsonResponse($response);

        $this->assertCorsResponse($response);

        $locationHeader = $response->headers->get('Location');
        $this->assertStringMatchesFormat('http://localhost/v1/users/%d', $locationHeader);
        $apiKey = $response->headers->get(Headers::API_KEY);
        // imperfect way to validate JWT
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

        return ['id' => $this->getNewId($response), 'apiKey' => $apiKey, 'name' => $body['name']];
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
            ['HTTP_ACCEPT' => 'application/json']
        );

        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringStartsWith('Parameter "name" of value "" violated a constraint', $jsonResponse['message'], $response->getContent());
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
        $this->assertStringStartsWith('Parameter "name" of value "   " violated a constraint', $jsonResponse['message'], $response->getContent());
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
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertGetSuccess($response);
        $this->assertEquals(['name' => trim($userData['name'])], $jsonResponse, $response);
        $this->assertJsonResponse($response);

        $this->assertCorsResponse($response);

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
            sprintf('/v1/users/%d', $userData['id']),
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
            sprintf('/v1/users/%d', $userData['id'] - 1),
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

    /**
     * @depends testAddUser
     * @param array $userData
     * @return array
     */
    public function testEditUserName(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $body = ['name' => " \tFunctional Test Edited \n"];

        $client->request(
            'PATCH',
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            json_encode($body)
        );
        $response = $client->getResponse();


        $this->assertEquals(204, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        $this->assertNotJsonResponse($response);

        $this->assertCorsResponse($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        // SELECT, START TRANSACTION, UPDATE, COMMIT
        $this->assertEquals(4, $doctrineDataCollector->getQueryCount());

        return array_merge($userData, $body);
    }

    /**
     * @depends testEditUserName
     * @param array $userData
     */
    public function testEditUserNameTrimmed(array $userData)
    {
        $client = static::createClient();

        $client->request(
            'GET',
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertNotEquals(['name' => $userData['name']], $jsonResponse, $response);
        $this->assertEquals(['name' => trim($userData['name'])], $jsonResponse, $response);
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testEditUserNameMissingNameError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'PATCH',
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringStartsWith('Parameter "name" of value "" violated a constraint', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testEditUserNameInvalidNameError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'PATCH',
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"name":"   "}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringStartsWith('Parameter "name" of value "   " violated a constraint', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testEditUserIncorrectUserIdError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'PATCH',
            '/v1/users/' . ($userData['id'] - 1),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"name":"Invalid"}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddUser
     * @param array $userData
     */
    public function testEditUserIncorrectApiKeyError(array $userData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'PATCH',
            sprintf('/v1/users/%d', $userData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer wrong2']
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);


        $this->assertEquals(401, $response->getStatusCode(), $response);
        $this->assertEquals(210, $jsonResponse['code'], $response->getContent());
        $this->assertEquals('API key `wrong2` is invalid', $jsonResponse['message'], $response->getContent());
    }
}
