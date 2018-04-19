<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\User;
use AppBundle\Security\ApiKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiTestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    /**
     * @var array
     */
    private static $usersData;

    /**
     * @var Game[]
     */
    private static $games;

    /**
     * @param int $userIndex
     * @return User
     */
    protected function getUser($userIndex)
    {
        $userData = $this->getUserData($userIndex);

        return $userData['user'];
    }

    /**
     * @param int $userIndex
     * @return string
     */
    protected function getUserApiKey($userIndex)
    {
        $userData = $this->getUserData($userIndex);

        return $userData['apiKey'];
    }

    /**
     * @param int $userIndex
     * @return array
     */
    private function getUserData($userIndex)
    {
        if (!isset(self::$usersData[$userIndex])) {
            self::$usersData[$userIndex] = $this->createUserData('Test User' . $userIndex);
        }

        return self::$usersData[$userIndex];
    }

    /**
     * @param string $name
     * @return array
     */
    private function createUserData($name)
    {
        $entityManager = $this->getEntityManager();
        $apiKeyManager = $this->getApiKeyManager();

        $user = new User();
        $user->setName($name);

        $entityManager->persist($user);
        $entityManager->flush();

        $apiKey = $apiKeyManager->generateApiKeyForUser($user);

        return ['user' => $user, 'apiKey' => $apiKey];
    }

    /**
     * @param int $userIndex
     * @param int $gameIndex
     * @return Game
     */
    protected function getGame($userIndex, $gameIndex)
    {
        if (!isset(self::$games[$gameIndex])) {
            $user = $this->getUser($userIndex);
            $this->getEntityManager()->persist($user);
            self::$games[$gameIndex] = $this->createGame($user);
        }

        return self::$games[$gameIndex];
    }

    /**
     * @param User $user
     * @param array $ships
     * @return Game
     */
    private function createGame(User $user, array $ships = [])
    {
        $entityManager = $this->getEntityManager();

        $game = new Game();
        $game
            ->setLoggedUser($user)
            ->setUser1($user)
            ->setPlayerShips($ships)
        ;

        $entityManager->persist($game);
        $entityManager->flush();

        return $game;
    }

    /**
     * @param Response $response
     */
    protected function assertGetJsonCors(Response $response)
    {
        $this->assertGetSuccess($response);
        $this->assertJsonResponse($response);
        $this->assertCorsResponse($response);
    }

    /**
     * @param Response $response
     */
    protected function assertGetSuccess(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode(), $response);
    }

    /**
     * @param Response $response
     */
    protected function assertJsonResponse(Response $response)
    {
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Missing "Content-Type: application/json" header'
        );
    }

    /**
     * @param Response $response
     */
    protected function assertNotJsonResponse(Response $response)
    {
        $this->assertFalse(
            $response->headers->contains('Content-Type', 'application/json'),
            'No need for "Content-Type: application/json" header'
        );
    }

    /**
     * @param Response $response
     */
    protected function assertCorsResponse(Response $response)
    {
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
    }

    /**
     * @param Response $response
     * @return int
     */
    protected function getNewId(Response $response)
    {
        preg_match('#/(\d+)$#', $response->headers->get('Location'), $match);

        return $match[1];
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * @return ApiKeyManager
     */
    protected function getApiKeyManager(): ApiKeyManager
    {
        return $this->getContainer()->get(ApiKeyManager::class);
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer()
    {
        // :/ - different container than the one use on request after another static::createClient() call
        if (!self::$container) {
            self::$container = static::createClient()->getContainer();
        }

        return self::$container;
    }
}
