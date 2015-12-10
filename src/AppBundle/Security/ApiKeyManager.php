<?php

namespace AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Exception\InvalidApiKeyException;
use Firebase\JWT\JWT;

class ApiKeyManager
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @param string $secret
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param User $user
     * @return string
     */
    public function generateApiKeyForUser(User $user)
    {
        $payload = [
            'id' => $user->getId(),
            'token' => $user->getToken()
        ];

        return JWT::encode($payload, $this->secret);
    }

    /**
     * @param $apiKey
     * @return \stdClass
     * @throws InvalidApiKeyException
     */
    public function getInfoFromApiKey($apiKey)
    {
        try {
            $jwtInfo = JWT::decode($apiKey, $this->secret, ['HS256']);
        } catch (\Exception $e) {
            throw new InvalidApiKeyException($apiKey, 0, $e);
        }

        return $jwtInfo;
    }
}
