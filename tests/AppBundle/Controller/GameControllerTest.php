<?php

namespace Tests\AppBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

class GameControllerTest extends AbstractApiTestCase
{
    /**
     * @return int
     */
    public function testAddGame()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']]
        );

        return $this->validateAddGame($client->getResponse(), $client->getProfile());
    }

    /**
     * @return array
     */
    public function testAddGameWithShips()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $body = [
            'playerShips' => ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']],
            json_encode($body)
        );

        $gameId = $this->validateAddGame($client->getResponse(), $client->getProfile());

        return ['id' => $gameId, 'ships' => $body['playerShips']];
    }

    /**
     * @param Response $response
     * @param Profile $profile
     * @return mixed
     */
    protected function validateAddGame(Response $response, Profile $profile)
    {
        $this->assertEquals(201, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        // @todo after every JSON request
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Missing "Content-Type: application/json" header'
        );

        // @todo check that after every request
        $this->assertCorsResponse($response);

        $locationHeader = $response->headers->get('Location');
        $this->assertStringMatchesFormat('http://localhost/v1/games/%d', $locationHeader);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(4, $doctrineDataCollector->getQueryCount());

        preg_match('#/(\d+)$#', $locationHeader, $match);

        return $match[1];
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameId, self::$userData['name']);
    }

    /**
     * @depends testAddGameWithShips
     * @param $gameDetails
     */
    public function testGetGameWithShips(array $gameDetails)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'GET',
            '/v1/games/' . $gameDetails['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameDetails['id'], self::$userData['name'], $gameDetails['ships']);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $body = [
            'playerShips' => ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']],
            json_encode($body)
        );
        $response = $client->getResponse();


        $this->assertEquals(204, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        // @todo after every JSON request
        $this->assertFalse(
            $response->headers->contains('Content-Type', 'application/json'),
            'No need for "Content-Type: application/json" header'
        );

        // @todo check that after every request
        $this->assertCorsResponse($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        // SELECT user, SELECT game, START TRANSACTION, SELECT event, UPDATE, COMMIT
        $this->assertEquals(6, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGameJoinGameError($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . self::$userData['apiKey']],
            '{"joinGame":true}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @param Response $response
     * @param Profile $profile
     */
    protected function validateGetGameCore(Response $response, Profile $profile)
    {
        $this->assertEquals(200, $response->getStatusCode(), $response);
        // @todo after every JSON request
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Missing "Content-Type: application/json" header'
        );

        // @todo check that after every request
        $this->assertCorsResponse($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param array $game
     * @param int $gameId
     * @param string $userName
     * @param array $playerShips
     * @param int $playerNumber
     * @param int $lessThanSecAgo
     */
    protected function validateGetGameResponse(
        array $game,
        $gameId,
        $userName,
        array $playerShips = [],
        $playerNumber = 1,
        $lessThanSecAgo = 60
    ) {
        $expectedKeys = ['id', 'player', 'playerShips', 'playerNumber', 'timestamp'];
        $gameKeys = array_keys($game);
        sort($expectedKeys);
        sort($gameKeys);
        $this->assertEquals($expectedKeys, $gameKeys);

        $this->assertEquals($gameId, $game['id'], 'Incorrect game id');
        $this->assertEquals(['name' => $userName], $game['player'], 'Incorrect player details');
        $this->assertEquals($playerShips, $game['playerShips'], 'Incorrect player ships');
        $this->assertEquals($playerNumber, $game['playerNumber'], 'Incorrect player number');
        // not more than X seconds ago
        $timestamp = new \DateTime($game['timestamp']);
        $this->assertLessThan($lessThanSecAgo, time() - $timestamp->getTimestamp());
    }
}
