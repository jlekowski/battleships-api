<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Security\ApiKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiTestCase extends WebTestCase
{
    /**
     * @var array
     */
    private static $user1Data;

    /**
     * @var array
     */
    private static $user2Data;

    /**
     * @return array
     */
    protected function getUser1Data()
    {
        if (empty(self::$user1Data)) {
            self::$user1Data = $this->createUserData('Test User1');
        }

        return self::$user1Data;
    }

    /**
     * @return array
     */
    protected function getUser2Data()
    {
        if (empty(self::$user2Data)) {
            self::$user2Data = $this->createUserData('Test User2');
        }

        return self::$user2Data;
    }

    /**
     * @param string $name
     * @return array
     */
    private function createUserData($name)
    {
        $container = static::createClient()->getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.default_entity_manager');
        /** @var ApiKeyManager $apiKeyManager */
        $apiKeyManager = $container->get('app.security.api_key_manager');

        $user = new User();
        $user->setName($name);

        $entityManager->persist($user);
        $entityManager->flush();

        $apiKey = $apiKeyManager->generateApiKeyForUser($user);

        return ['id' => $user->getId(), 'apiKey' => $apiKey, 'name' => $user->getName()];
    }

    /**
     * @param Response $response
     */
    public function assertCorsResponse(Response $response)
    {
        // @todo check that after every request
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
}
