<?php

namespace Tests\AppBundle\Controller;

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
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'), $response->headers);

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
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'), $response->headers);
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
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'), $response->headers);
    }
}
