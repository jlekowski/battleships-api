<?php

namespace Tests\AppBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

class CorsControllerTest extends AbstractApiTestCase
{
    public function testOptionsUsers()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/users');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsUsersIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/users', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    public function testOptionsUser()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/users/1');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsUserIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/users/1', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    public function testOptionsGames()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/games');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsGamesIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/games', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    public function testOptionsGame()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/games/1');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsGameIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/games/1', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    public function testOptionsEvents()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/games/1/events');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsEventsIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/games/1/events', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    public function testOptionsEvent()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v1/games/1/events/1');
        $response = $client->getResponse();

        $this->validateCorsResponse($response, $client->getProfile());
    }

    public function testOptionsEventIncorrectVersionError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('OPTIONS', '/v2/games/1/events/1', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->validateCorsResponseIncorrectVersionError($response, $client->getProfile());
    }

    /**
     * @param Response $response
     * @param Profile $profile
     */
    protected function validateCorsResponse(Response $response, Profile $profile)
    {
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode(), $response);
        $this->assertNull($response->headers->get('Content-Type'), 'Unnecessary "Content-Type" header');
        $this->assertCorsResponse($response);

        $this->validateCorsResponseDbCount($profile);
    }

    /**
     * @param Response $response
     * @param Profile $profile
     */
    protected function validateCorsResponseIncorrectVersionError(Response $response, Profile $profile)
    {
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode(), $response);
        $this->assertEquals(404, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('No route found for "OPTIONS /v2/%s"', $jsonResponse['message'], $response->getContent());

        $this->validateCorsResponseDbCount($profile);
    }

    /**
     * @param Profile $profile
     */
    protected function validateCorsResponseDbCount(Profile $profile)
    {
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(0, $doctrineDataCollector->getQueryCount());
    }
}
