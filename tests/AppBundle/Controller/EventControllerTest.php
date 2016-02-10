<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Event;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo test more get events with filter (including incorrect values)
 * @todo test more add events (including incorrect values and shot result)
 */
class EventControllerTest extends AbstractApiTestCase
{
    public function testGetEventEmpty()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey($userIndex);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
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
        $client->enableProfiler();

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
        $client->enableProfiler();

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

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events', $game->getId()),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );
        $response = $client->getResponse();
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

    public function testGetEventsInvalidUserError()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $userIndex = 1;
        $game = $this->getGame($userIndex, 1);
        $apiKey = $this->getUserApiKey(2);

        $client->request(
            'GET',
            sprintf('/v1/games/%d/events', $game->getId()),
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
}
