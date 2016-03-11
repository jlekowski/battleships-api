<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

class EventControllerTest extends AbstractApiTestCase
{
    public function testGetEventEmpty()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $response = $this->callGetEvents($client, $game, $apiKey);
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $jsonResponse);
        $this->assertEmpty($jsonResponse, 'Expected to get no events');
        $this->assertGetJsonCors($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(3, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @return array
     */
    public function testAddEventChat()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_CHAT,
            'value' => 'test chat'
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(5, $doctrineDataCollector->getQueryCount());

        $this->assertCount(1, $jsonResponse, 'Expected only timestamp in response');
        $this->assertArrayHasKey('timestamp', $jsonResponse, 'Expected to have timestamp in response');
        // not more than X seconds ago
        $timestamp = new \DateTime($jsonResponse['timestamp']);
        $this->assertLessThan(60, time() - $timestamp->getTimestamp());

        return array_merge($body, ['id' => $this->getNewId($response), 'timestamp' => $jsonResponse['timestamp'], 'player' => $userIndex]);
    }

    /**
     * @depends testAddEventChat
     * @param array $eventData
     */
    public function testGetEvent(array $eventData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events/%d', $game->getId(), $eventData['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals($eventData, $jsonResponse);
        $this->assertGetJsonCors($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(3, $doctrineDataCollector->getQueryCount());
    }


    /**
     * @depends testAddEventChat
     * @param array $eventData
     */
    public function testGetEventInvalidUserError(array $eventData)
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events/%d', $game->getId(), $eventData['id']),
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
     * @depends testAddEventChat
     * @param array $eventData
     */
    public function testGetEventInvalidGameError(array $eventData)
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 2);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events/%d', $game->getId(), $eventData['id']),
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
     * @depends testAddEventChat
     * @param array $eventData
     */
    public function testGetEvents(array $eventData)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $response = $this->callGetEvents($client, $game, $apiKey);
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals([$eventData], $jsonResponse);
        $this->assertGetJsonCors($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(3, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @return array
     */
    public function testAddEventJoinGame()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_JOIN_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event, START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(6, $doctrineDataCollector->getQueryCount());

        $this->assertEquals('', $response->getContent(), $response);

        return array_merge($body, ['id' => $this->getNewId($response), 'value' => true, 'player' => $userIndex]);
    }

    /**
     * @depends testAddEventJoinGame
     * @param array $eventDataJoinGame
     */
    public function testAddEventJoinGameDuplicateError(array $eventDataJoinGame)
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_JOIN_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(190, $jsonResponse['code'], $response->getContent());
        $this->assertEquals(sprintf('Event `%s` has already been created', $body['type']), $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddEventJoinGame
     * @depends testAddEventChat
     * @param array $eventDataJoinGame
     * @param array $eventDataChat
     */
    public function testGetEventsByTypeJoinGame(array $eventDataJoinGame, array $eventDataChat)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?type=%s', $game->getId(), Event::TYPE_JOIN_GAME),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $timestamp = new \DateTime($jsonResponse[0]['timestamp']);
        unset($jsonResponse[0]['timestamp']);
        $this->assertEquals([$eventDataJoinGame], $jsonResponse);
        // not more than X seconds ago
        $this->assertLessThan(60, time() - $timestamp->getTimestamp());
        $this->assertGetJsonCors($response);

        $profile = $client->getProfile();
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $profile->getCollector('db');
        $this->assertEquals(3, $doctrineDataCollector->getQueryCount());
    }

    /**
     * @return array
     */
    public function testAddEventNameUpdate()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_NAME_UPDATE,
            'value' => " \nTest User Updated \t"
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(5, $doctrineDataCollector->getQueryCount());

        $this->assertEquals('', $response->getContent(), $response);

        return array_merge($body, ['id' => $this->getNewId($response)]);
    }

    /**
     * @depends testAddEventNameUpdate
     * @param array $eventDataNameUpdate
     */
    public function testAddEventNameUpdateTrimmed(array $eventDataNameUpdate)
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events/%d', $game->getId(), $eventDataNameUpdate['id']),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertNotEquals($eventDataNameUpdate['value'], $jsonResponse['value']);
        $this->assertEquals(trim($eventDataNameUpdate['value']), $jsonResponse['value']);
    }

    // at the moment it adds name_update event with value 1
//    public function testAddEventNameUpdateMissingNameError()
//    {
//        $client = static::createClient();
//
//        $userIndex = 1;
//        $game = $this->getGame($userIndex, 1);
//        $apiKey = $this->getUserApiKey($userIndex);
//
//        $body = [
//            'type' => Event::TYPE_NAME_UPDATE
//        ];
//        $client->request(
//            'POST',
//            sprintf('/v1/games/%d/events', $game->getId()),
//            [],
//            [],
//            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
//            json_encode($body)
//        );
//        $response = $client->getResponse();
//        $jsonResponse = json_decode($response->getContent(), true);
//
//        $this->assertEquals(400, $response->getStatusCode(), $response);
//        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
//        $this->assertStringMatchesFormat(
//            'Request parameter value value \'\' violated a constraint %s',
//            $jsonResponse['message'],
//            $response->getContent()
//        );
//    }

    public function testAddEventNameUpdateEmptyNameError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_NAME_UPDATE,
            'value' => ''
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
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
            'Request parameter value value \'\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testAddEventNameUpdateIncorrectNameError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_NAME_UPDATE,
            'value' => "  \n\t  "
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
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
            'Request parameter value value \'%a\' violated a constraint %a',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    /**
     * @return array
     */
    public function testAddEventStartGame()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 2;
        $gameIndex = 3;
        $game = $this->getGame($userIndex, $gameIndex);
        $game->setPlayerShips(['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']);
        $entityManager = $this->getEntityManager();
        $entityManager->persist($game);
        $entityManager->flush();
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_START_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event, START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(6, $doctrineDataCollector->getQueryCount());

        $this->assertEquals('', $response->getContent(), $response);

        return ['userIndex' => $userIndex, 'gameIndex' => $gameIndex];
    }

    /**
     * @depends testAddEventStartGame
     * @param array $eventDetails
     */
    public function testAddEventStartGameDuplicateError(array $eventDetails)
    {
        $client = static::createClient();

        $userIndex = $eventDetails['userIndex'];
        $game = $this->getGame($userIndex, $eventDetails['gameIndex']);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_START_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(190, $jsonResponse['code'], $response->getContent());
        $this->assertEquals(sprintf('Event `%s` has already been created', $body['type']), $jsonResponse['message'], $response->getContent());
    }

    public function testAddEventStartGameNoShipsError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_START_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(409, $response->getStatusCode(), $response);
        $this->assertEquals(170, $jsonResponse['code'], $response->getContent());
        $this->assertEquals('You must set ships first', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @return Game
     */
    public function testAddEventShot()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $user1 = $this->getUser(3);
        $user2 = $this->getUser(4);
        $game = new Game();
        $game
            ->setLoggedUser($user1)
            ->setUser1($user1)
            ->setUser2($user2)
            ->setUser1Ships(['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'])
            ->setUser2Ships(['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'])
        ;
        // starting game
        $event1 = new Event();
        $event1
            ->setGame($game)
            ->setPlayer(1)
            ->setType(Event::TYPE_START_GAME)
            ->setValue(true)
        ;
        $event2 = new Event();
        $event2
            ->setGame($game)
            ->setPlayer(2)
            ->setType(Event::TYPE_START_GAME)
            ->setValue(true)
        ;

        $entityManager = $this->getEntityManager();
        $entityManager->persist($game);
        $entityManager->persist($event1);
        $entityManager->persist($event2);
        $entityManager->flush();

        $apiKey = $this->getUserApiKey(3);

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => $game->getUser2Ships()[0]
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event (getAttackerShots), SELECT event (has game started), SELECT event (whoseTurn), START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(8, $doctrineDataCollector->getQueryCount());

        $this->assertEquals(['result' => BattleManager::SHOT_RESULT_SUNK], $jsonResponse, $response);

        return $game;
    }

    /**
     * @depends testAddEventShot
     * @param Game $game
     * @return Game
     */
    public function testAddEventShotFollowupMiss(Game $game)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => 'A2'
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event (has game started), SELECT event (whoseTurn), START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(7, $doctrineDataCollector->getQueryCount());

        $this->assertEquals(['result' => BattleManager::SHOT_RESULT_MISS], $jsonResponse, $response);

        return $game;
    }

    /**
     * @depends testAddEventShotFollowupMiss
     * @param Game $game
     */
    public function testAddEventShotNotMyTurnError(Game $game)
    {
        $client = static::createClient();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => $game->getOtherShips()[1]
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(409, $response->getStatusCode(), $response);
        $this->assertEquals(170, $jsonResponse['code'], $response->getContent());
        $this->assertEquals('It\'s other player\'s turn', $jsonResponse['message'], $response->getContent());
    }


    /**
     * @depends testAddEventShotFollowupMiss
     * @param Game $game
     */
    public function testAddEventShotIncorrectCoordError(Game $game)
    {
        $client = static::createClient();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => 'A11'
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(160, $jsonResponse['code'], $response->getContent());
        $this->assertEquals(sprintf('Invalid coordinates provided: %s', $body['value']), $jsonResponse['message'], $response->getContent());
    }

    /**
     * @depends testAddEventShotFollowupMiss
     * @param Game $game
     * @return Game
     */
    public function testAddEventShotChangedTurnHit(Game $game)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser2());

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => $game->getPlayerShips()[1]
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event (getAttackerShots), SELECT event (has game started), SELECT event (whoseTurn), START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(8, $doctrineDataCollector->getQueryCount());

        $this->assertEquals(['result' => BattleManager::SHOT_RESULT_HIT], $jsonResponse, $response);

        return $game;
    }

    /**
     * @depends testAddEventShotChangedTurnHit
     * @param Game $game
     */
    public function testAddEventShotChangedTurnHitFollowUpSunk(Game $game)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser2());

        $body = [
            'type' => Event::TYPE_SHOT,
            'value' => $game->getPlayerShips()[2]
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event (getAttackerShots), SELECT event (has game started), SELECT event (whoseTurn), START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(8, $doctrineDataCollector->getQueryCount());

        $this->assertEquals(['result' => BattleManager::SHOT_RESULT_SUNK], $jsonResponse, $response);
    }

    /**
     * @return array
     */
    public function testAddEventNewGame()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_NEW_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();

        $this->validateAddEvent($response);
        /** @var DoctrineDataCollector $doctrineDataCollector */
        $doctrineDataCollector = $client->getProfile()->getCollector('db');
        // SELECT user, SELECT game, SELECT event, START TRANSACTION, INSERT event, COMMIT
        $this->assertEquals(6, $doctrineDataCollector->getQueryCount());

        $this->assertEquals('', $response->getContent(), $response);
    }

    /**
     * @depends testAddEventNewGame
     */
    public function testAddEventNewGameDuplicateError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => Event::TYPE_NEW_GAME
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey],
            json_encode($body)
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(190, $jsonResponse['code'], $response->getContent());
        $this->assertEquals(sprintf('Event `%s` has already been created', $body['type']), $jsonResponse['message'], $response->getContent());
    }

    public function testAddEventMissingTypeError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'value' => true
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
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
            'Request parameter "type" is empty',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testAddEventEmptyTypeError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => ''
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
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
            'Request parameter type value \'\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testAddEventIncorrectTypeError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $body = [
            'type' => 'Incorrect'
        ];
        $client->request(
            'POST',
            sprintf('/v1/games/%d/events', $game->getId()),
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
            'Request parameter type value \'Incorrect\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    /**
     * @depends testAddEventShot
     * @param Game $game
     * @return array
     */
    public function testGetEventsByIdGreaterThan(Game $game)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $response = $this->callGetEvents($client, $game, $apiKey);
        $allEvents = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $this->assertNotEmpty($allEvents);

        $lastEvent = end($allEvents);
        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?gt=%s', $game->getId(), ($lastEvent['id'] - 1)),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $this->assertEquals([$lastEvent], $jsonResponse);

        return $allEvents;
    }

    /**
     * @depends testAddEventShot
     * @depends testGetEventsByIdGreaterThan
     * @param Game $game
     * @param array $allEvents
     */
    public function testGetEventsByTypeStartGame(Game $game, array $allEvents)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?type=%s', $game->getId(), Event::TYPE_START_GAME),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $filteredEVents = $this->filterEventsByField($allEvents, 'type', Event::TYPE_START_GAME);
        $this->assertEquals($filteredEVents, $jsonResponse);
    }

    /**
     * @depends testAddEventShot
     * @depends testGetEventsByIdGreaterThan
     * @param Game $game
     * @param array $allEvents
     */
    public function testGetEventsByTypeShot(Game $game, array $allEvents)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?type=%s', $game->getId(), Event::TYPE_SHOT),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $filteredEVents = $this->filterEventsByField($allEvents, 'type', Event::TYPE_SHOT);
        $this->assertEquals($filteredEVents, $jsonResponse);
    }

    /**
     * @depends testAddEventShot
     * @depends testGetEventsByIdGreaterThan
     * @param Game $game
     * @param array $allEvents
     */
    public function testGetEventsByPlayer(Game $game, array $allEvents)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $playerNumber = 2;
        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?player=%s', $game->getId(), $playerNumber),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $filteredEVents = $this->filterEventsByField($allEvents, 'player', $playerNumber);
        $this->assertEquals($filteredEVents, $jsonResponse);
    }

    /**
     * @depends testAddEventShot
     * @depends testGetEventsByIdGreaterThan
     * @param Game $game
     * @param array $allEvents
     */
    public function testGetEventsByIdGreaterThanTypeShotAndPlayer(Game $game, array $allEvents)
    {
        $client = static::createClient();
        $client->enableProfiler();

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($game->getUser1());

        $firstEvent = array_shift($allEvents);
        $playerNumber = 1;
        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?gt=%s&type=%s&player=%s', $game->getId(), $firstEvent['id'], Event::TYPE_SHOT, $playerNumber),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertGetJsonCors($response);
        $filteredEVents = $this->filterEventsByField($allEvents, 'type', Event::TYPE_SHOT);
        $filteredEVents = $this->filterEventsByField($filteredEVents, 'player', $playerNumber);
        $this->assertEquals($filteredEVents, $jsonResponse);
    }

    public function testGetEventsByIdGreaterThanIncorrectError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?gt=%s', $game->getId(), '1a'),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat(
            'Query parameter gt value \'1a\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testGetEventsByTypeIncorrectError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?type=%s', $game->getId(), 'Incorrect'),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat(
            'Query parameter type value \'Incorrect\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testGetEventsByPlayerIncorrectError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events?player=%s', $game->getId(), 3),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), $response);
        $this->assertEquals(400, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat(
            'Query parameter player value \'3\' violated a constraint %s',
            $jsonResponse['message'],
            $response->getContent()
        );
    }

    public function testGetEventsInvalidUserError()
    {
        $client = static::createClient();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey(2);

        $response = $this->callGetEvents($client, $game, $apiKey);
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode(), $response);
        $this->assertEquals(403, $jsonResponse['code'], $response->getContent());
        $this->assertStringMatchesFormat('Expression "%s" denied access.', $jsonResponse['message'], $response->getContent());
    }

    /**
     * @param Response $response
     */
    protected function validateAddEvent(Response $response)
    {
        $this->assertEquals(201, $response->getStatusCode(), $response);

        $this->assertJsonResponse($response);
        $this->assertCorsResponse($response);

        $locationHeader = $response->headers->get('Location');
        $this->assertStringMatchesFormat('http://localhost/v1/games/%d/events/%d', $locationHeader);
    }

    /**
     * @param array $events
     * @param string $eventField
     * @param string|int $eventFieldValue
     * @return array
     */
    private function filterEventsByField(array $events, $eventField, $eventFieldValue)
    {
        return array_values(array_filter($events, function ($event) use ($eventField, $eventFieldValue) {
            return $event[$eventField] === $eventFieldValue ? $event : null;
        }));
    }

    /**
     * @param Client $client
     * @param Game $game
     * @param string $apiKey
     * @return Response
     */
    private function callGetEvents(Client $client, Game $game, $apiKey)
    {
        $client->request(
            'GET',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );

        return $client->getResponse();
    }
}
