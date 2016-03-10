<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\User;
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
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );

        return $this->validateAddGame($client->getResponse(), $client->getProfile());
    }

    public function testAddGameWithEmptyShips()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $body = [
            'playerShips' => []
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );

        $this->validateAddGame($client->getResponse(), $client->getProfile());
    }

    /**
     * @return array
     */
    public function testAddGameWithShips()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $body = [
            'playerShips' => ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );

        $gameId = $this->validateAddGame($client->getResponse(), $client->getProfile(), true);

        return ['id' => $gameId, 'ships' => $body['playerShips']];
    }

    public function testAddGameWithShipsInvalidCoordError()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $body = [
            'playerShips' => ['A11']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
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
        $apiKey = $this->getUserApiKey(1);

        $body = [
            'playerShips' => ['B2','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'POST',
            '/v1/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
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
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameId, $this->getUser(1));
    }

    /**
     * @depends testAddGameWithShips
     * @param $gameDetails
     */
    public function testGetGameWithShips(array $gameDetails)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games/' . $gameDetails['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameDetails['id'], $this->getUser(1), $gameDetails['ships']);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGameNotExistingGameError($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games/' . ($gameId + 100),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode(), $response);
        $this->assertEquals(404, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('AppBundle\\Entity\\Game object not found.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGameInvalidGameSuccess($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games/' . ($gameId . 'b'),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile());
        $this->validateGetGameResponse($jsonResponse, $gameId, $this->getUser(1));
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGameInvalidGameError($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games/' . ('b' . $gameId),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode(), $response);
        $this->assertEquals(404, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('AppBundle\\Entity\\Game object not found.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGameUser2($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateGetGameCore($response, $client->getProfile(), true);
        $this->validateGetGameResponse($jsonResponse, $gameId, null, [], $this->getUser(1), 2);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGame($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $body = [
            'playerShips' => ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateEditGameCore($response, $client->getProfile());
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testEditGameJoinGameError($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
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
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            '{"joinGame":true}'
        );
        $response = $client->getResponse();

        $this->validateEditGameCore($response, $client->getProfile());

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
        $apiKey = $this->getUserApiKey(3);

        $client->request(
            'GET',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
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
        $apiKey = $this->getUserApiKey(3);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
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
        $apiKey = $this->getUserApiKey(3);

        $client->request(
            'PATCH',
            '/v1/games/' . $gameId,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
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
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'GET',
            '/v1/games',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
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
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'GET',
            '/v1/games?available=true',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $jsonResponse);
        $this->assertNotEmpty($jsonResponse, 'Expected to get at least 1 game available');
        $this->validateGetGamesCore($response, $client->getProfile());
        $this->validateGetGameResponse(reset($jsonResponse), $gameDetails['id'], null, [], $this->getUser(1), 2);
    }

    /**
     * @depends testAddGame
     * @param int $gameId
     */
    public function testGetGamesAvailableUser1($gameId)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(1);

        $client->request(
            'GET',
            '/v1/games?available=true',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
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
     * @depends testAddGameWithShips
     * @param array $gameDetails
     */
    public function testEditGameJoinGameAndShips(array $gameDetails)
    {
        $client = static::createClient();
        $client->enableProfiler();
        $apiKey = $this->getUserApiKey(2);

        $body = [
            'joinGame' => true,
            'playerShips' => ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        ];
        $client->request(
            'PATCH',
            '/v1/games/' . $gameDetails['id'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateEditGameCore($response, $client->getProfile(), 2);
    }

    /**
     * @param Response $response
     * @param Profile $profile
     * @param bool $withShips
     * @return int
     */
    protected function validateAddGame(Response $response, Profile $profile, $withShips = false)
    {
        $this->assertEquals(201, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        $this->assertJsonResponse($response);

        $this->assertCorsResponse($response);

        $locationHeader = $response->headers->get('Location');
        $this->assertStringMatchesFormat('http://localhost/v1/games/%d', $locationHeader);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals($withShips ? 6 : 4, $doctrineDataCollector->getQueryCount());

        return $this->getNewId($response);
    }

    /**
     * @param Response $response
     * @param Profile $profile
     * @param bool $otherUser
     */
    protected function validateGetGameCore(Response $response, Profile $profile, $otherUser = false)
    {
        $this->assertGetJsonCors($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals($otherUser ? 3 : 2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param Response $response
     * @param Profile $profile
     * @param int $paramCount
     */
    protected function validateEditGameCore(Response $response, Profile $profile, $paramCount = 1)
    {
        $this->assertEquals(204, $response->getStatusCode(), $response);
        $this->assertEquals('', $response->getContent(), $response);
        $this->assertFalse(
            $response->headers->contains('Content-Type', 'application/json'),
            'No need for "Content-Type: application/json" header'
        );

        $this->assertCorsResponse($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        // SELECT user, SELECT game, SELECT event * $paramCount (unique), START TRANSACTION, INSERT event * $paramCount, UPDATE game, COMMIT
        $this->assertEquals(5 + $paramCount * 2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param Response $response
     * @param Profile $profile
     */
    protected function validateGetGamesCore(Response $response, Profile $profile)
    {
        $this->assertGetJsonCors($response);

        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        // SELECT user1, SELECT game, [... SELECT user (available game)]
        $this->assertGreaterThanOrEqual(2, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @param array $game
     * @param int $gameId
     * @param User $user
     * @param array $playerShips
     * @param User $otherUser
     * @param int $playerNumber
     * @param int $lessThanAgo (in seconds)
     */
    protected function validateGetGameResponse(
        array $game,
        $gameId,
        User $user = null,
        array $playerShips = [],
        User $otherUser = null,
        $playerNumber = 1,
        $lessThanAgo = 120
    ) {
        $expectedKeys = ['id', 'playerShips', 'playerNumber', 'timestamp'];
        if ($user) {
            $expectedKeys[] = 'player';
        }
        if ($otherUser) {
            $expectedKeys[] = 'other';
        }
        $gameKeys = array_keys($game);
        sort($expectedKeys);
        sort($gameKeys);
        $this->assertEquals($expectedKeys, $gameKeys);

        $this->assertEquals($gameId, $game['id'], 'Incorrect game id');
        if ($user) {
            $this->assertEquals(['name' => $user->getName()], $game['player'], 'Incorrect player details');
        }
        $this->assertEquals($playerShips, $game['playerShips'], 'Incorrect player ships');
        $this->assertEquals($playerNumber, $game['playerNumber'], 'Incorrect player number');
        if ($otherUser) {
            $this->assertEquals(['name' => $otherUser->getName()], $game['other'], 'Incorrect other details');
        }
        // not more than X seconds ago
        $timestamp = new \DateTime($game['timestamp']);
        $this->assertLessThan($lessThanAgo, time() - $timestamp->getTimestamp());
    }
}
