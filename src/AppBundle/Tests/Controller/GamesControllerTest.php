<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GamesControllerTest extends WebTestCase
{
    public function testGetGameAction()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/v1/games/2', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'), $response->headers);

        $profile = $client->getProfile();
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
//        echo "<pre>";
//        print_r($profile->getCollector('memory'));
//        exit;

        $content = json_decode($response->getContent(), true);
        $this->validateGameResponse($content);

        // check the number of requests
        $this->assertEquals(1, $profile->getCollector('db')->getQueryCount());
        // check the time spent in the framework
        $this->assertLessThan(5000, $profile->getCollector('time')->getDuration());
    }

    /**
     * @param array $game
     */
    protected function validateGameResponse(array $game)
    {
        $expectedKeys = ['player1_hash', 'player2_hash', 'player1_name', 'player2_name', 'player1_ships', 'player2_ships'];
        $gameKeys = array_keys($game);
        sort($expectedKeys);
        sort($gameKeys);
        $this->assertEquals($expectedKeys, $gameKeys);

        foreach ($game as $key => $value) {
            switch ($key) {
                case 'player1_hash':
                case 'player2_hash':
                    $this->assertEquals(32, mb_strlen($value));
                    break;

                case 'player1_name':
                case 'player2_name':
                    $this->assertInternalType('string', $value);
                    $this->assertNotEmpty($value);
                    break;

                case 'player1_ships':
                case 'player2_ships':
                    $this->assertCount(0, $value);
                    break;
            }
        }
    }
}
