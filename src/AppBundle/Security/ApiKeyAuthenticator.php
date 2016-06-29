<?php

namespace AppBundle\Security;

use AppBundle\Exception\InvalidApiKeyException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    /**
     * @var ApiKeyManager
     */
    protected $apiKeyManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ServerBag
     */
    protected $serverBag;

    /**
     * @param ApiKeyManager $apiKeyManager
     * @param LoggerInterface $logger
     */
    public function __construct(ApiKeyManager $apiKeyManager, LoggerInterface $logger)
    {
        $this->apiKeyManager = $apiKeyManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function createToken(Request $request, $providerKey)
    {
        preg_match('/^Bearer (\S+)$/', $request->headers->get('Authorization'), $matches);
        $apiKey = isset($matches[1]) ? $matches[1] : null;

        if (!$apiKey) {
            throw new BadCredentialsException('No API key found');
        }
        $this->serverBag = $request->server;

        return new PreAuthenticatedToken('anon.', $apiKey, $providerKey);
    }

    /**
     * @inheritdoc
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof ApiKeyUserProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiKeyUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $apiKey = $token->getCredentials();
        try {
            $apiKeyInfo = $this->apiKeyManager->getInfoFromApiKey($apiKey);
        } catch (\Exception $e) {
            $this->logger->error('Someone is trying to fake the token', [$this->serverBag]);
            throw new InvalidApiKeyException($apiKey, 0, $e);
        }

        $user = $userProvider->loadUserById($apiKeyInfo->id);
        if ($apiKeyInfo->token !== $user->getToken()) {
            $this->logger->alert('Someone found the JWT secret and is trying to fake the token', [$this->serverBag]);
            throw new InvalidApiKeyException($apiKey);
        }

        return new PreAuthenticatedToken($user, $apiKey, $providerKey, $user->getRoles());
    }

    /**
     * @inheritdoc
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // not to throw AuthenticationCredentialsNotFoundException and use ExceptionController
        throw $exception;
    }
}
