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
        $userData = $this->getUserData(1);

        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
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
        $userData = $this->getUserData(1);

        $body = [
            'playerShips' => ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            json_encode($body)
        );

        $gameId = $this->validateAddGame($client->getResponse(), $client->getProfile());

        return ['id' => $gameId, 'ships' => $body['playerShips']];
    }

    public function testAddGameWithShipsInvalidCoordError()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $body = [
            'playerShips' => ['A11']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat(
            'Request parameter playerShips value \'A11\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testAddGameWithShipsInvalidShipsError()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $body = [
            'playerShips' => ['B2','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(150, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Number of ships\' types is incorrect', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameId, $userData);
    }

    /**
     * @depends testAddGameWithShips
     * @param $gameDetails
     */
    public function testGetGameWithShips(array $gameDetails)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $client->request(
            'GET',
            '/v1/games/' . $gameDetails['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameDetails['id'], $userData, $gameDetails['ships']);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGameUser2($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(2);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile(), true);
        $this->validateGetGameResponse($jsonResponse, $gameId, [], [], $this->getUserData(1), 2);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $body = [
            'playerShips' => ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
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
        // SELECT user, SELECT game, SELECT event, START TRANSACTION, INSERT event, UPDATE game, COMMIT
        $this->assertEquals(7, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGameJoinGameError($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"joinGame":true}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     * @return int
     */
    public function testEditGameJoinGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(2);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"joinGame":true}'
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
        // SELECT user, SELECT game, SELECT event (join_game), START TRANSACTION, INSERT INTO, UPDATE, COMMIT
        $this->assertEquals(7, $doctrineDataCollector->getQueryCount());

        return $gameId;
    }

    /**
     * @depends testEditGameJoinGame
     * @param int $gameId
     */
    public function testGetGameUser3Error($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(3);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
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
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGameUser3Error($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(3);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"playerShips":[]}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGameJoinGameUser3Error($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(3);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']],
            '{"playerShips":[]}'
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     *
     */
    public function testGetGames()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(2);

        $client->request(
            'GET',
            '/v1/games',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Invalid request - must use \'?available=true\'', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGameWithShips
     * @param array $gameDetails
     */
    public function testGetGamesAvailable(array $gameDetails)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(2);

        $client->request(
            'GET',
            '/v1/games?available=true',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $jsonResponse);
        $this->assertNotEmpty($jsonResponse, 'Expected to get at least 1 game available');
        $this->validateGetGamesCore($response, $client->getProfile());
        $this->validateGetGameResponse(reset($jsonResponse), $gameDetails['id'], [], [], $this->getUserData(1), 2);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGamesAvailableUser1($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $userData = $this->getUserData(1);

        $client->request(
            'GET',
            '/v1/games?available=true',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['apiKey']]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $jsonResponse);
        $this->validateGetGamesCore($response, $client->getProfile());
        foreach ($jsonResponse as $game) {
            $this->assertNotEquals($gameId, $game['id']);
        }
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
     * @param Response $response
     */
    protected function validateGetJsonCore(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode(), $response);
        // @todo after every JSON request
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Missing "Content-Type: application/json" header'
        );

        // @todo check that after every request
        $this->assertCorsResponse($response);
    }

    /**
     * @param Response $response
     * @param Profile $profile
     * @param bool $otherUser
     */
    protected function validateGetGameCore(Response $response, Profile $profile, $otherUser = false)
    {
        $this->validateGetJsonCore($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals($otherUser ? 3 : 2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param Response $response
     * @param Profile $profile
     */
    protected function validateGetGamesCore(Response $response, Profile $profile)
    {
        $this->validateGetJsonCore($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        // SELECT user1, SELECT game, [... SELECT user (available game)]
        $this->assertGreaterThanOrEqual(2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param array $game
     * @param int $gameId
     * @param array $userData
     * @param array $playerShips
     * @param array $otherUserData
     * @param int $playerNumber
     * @param int $lessThanAgo (in seconds)
     */
    protected function validateGetGameResponse(
        array $game,
        $gameId,
        array $userData,
        array $playerShips = [],
        array $otherUserData = [],
        $playerNumber = 1,
        $lessThanAgo = 60
    ) {
        $expectedKeys = ['id', 'playerShips', 'playerNumber', 'timestamp'];
        if ($userData) {
            $expectedKeys[] = 'player';
        }
        if ($otherUserData) {
            $expectedKeys[] = 'other';
        }
        $gameKeys = array_keys($game);
        sort($expectedKeys);
        sort($gameKeys);
        $this->assertEquals($expectedKeys, $gameKeys);

        $this->assertEquals($gameId, $game['id'], 'Incorrect game id');
        if ($userData) {
            $this->assertEquals(['name' => $userData['name']], $game['player'], 'Incorrect player details');
        }
        $this->assertEquals($playerShips, $game['playerShips'], 'Incorrect player ships');
        $this->assertEquals($playerNumber, $game['playerNumber'], 'Incorrect player number');
        if ($otherUserData) {
            $this->assertEquals(['name' => $otherUserData['name']], $game['other'], 'Incorrect other details');
        }
        // not more than X seconds ago
        $timestamp = new \DateTime($game['timestamp']);
        $this->assertLessThan($lessThanAgo, time() - $timestamp->getTimestamp());
    }
}
