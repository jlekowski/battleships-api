<?php

namespace Tests\AppBundle\Security;

use AppBundle\Security\ApiKeyAuthenticator;
use AppBundle\Security\ApiKeyManager;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Role\Role;

class ApiKeyAuthenticatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApiKeyAuthenticator
     */
    protected $apiKeyAuthenticator;

    /**
     * @var ApiKeyManager
     */
    protected $apiKeyManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setUp()
    {
        $this->apiKeyManager = $this->prophesize('AppBundle\Security\ApiKeyManager');
        $this->logger = $this->prophesize('Psr\Log\LoggerInterface');
        $this->apiKeyAuthenticator = new ApiKeyAuthenticator($this->apiKeyManager->reveal(), $this->logger->reveal());
    }

    public function testCreateToken()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $headerBag = $this->prophesize('Symfony\Component\HttpFoundation\HeaderBag');

        $request->headers = $headerBag;
        $headerBag->get('Authorization')->willReturn('Bearer a2,.@#$%)*/\!');

        /** @var PreAuthenticatedToken $token */
        $token = $this->apiKeyAuthenticator->createToken($request->reveal(), 'key');
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken', $token);
        $this->assertEquals('anon.', $token->getUser());
        $this->assertEquals('a2,.@#$%)*/\!', $token->getCredentials());
        $this->assertEquals('key', $token->getProviderKey());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage No API key found
     */
    public function testCreateTokenThrowsExceptionWhenInvalidApiKey()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $headerBag = $this->prophesize('Symfony\Component\HttpFoundation\HeaderBag');

        $request->headers = $headerBag;
        $headerBag->get('Authorization')->willReturn('Bearer a b');

        $this->apiKeyAuthenticator->createToken($request->reveal(), 'key');
    }

    public function testAuthenticateToken()
    {
        $userProvider = $this->prophesize('AppBundle\Security\ApiKeyUserProvider');
        $oldToken = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user = $this->prophesize('AppBundle\Entity\User');

        $oldToken->getCredentials()->willReturn('apiKey');
        $apiKeyInfo = new \stdClass();
        $apiKeyInfo->id = 1;
        $apiKeyInfo->token = 'userToken';
        $this->apiKeyManager->getInfoFromApiKey('apiKey')->willReturn($apiKeyInfo);
        $userProvider->loadUserById(1)->willReturn($user);
        $user->getToken()->willReturn('userToken');
        $roles = ['ROLE_TEST'];
        $user->getRoles()->willReturn($roles);

        $token = $this->apiKeyAuthenticator->authenticateToken($oldToken->reveal(), $userProvider->reveal(), 'key');
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken', $token);
        $this->assertEquals($user->reveal(), $token->getUser());
        $this->assertEquals('apiKey', $token->getCredentials());
        $this->assertEquals('key', $token->getProviderKey());
        /** @var Role $role */
        foreach ($token->getRoles() as $key => $role) {
            $this->assertEquals($roles[$key], $role->getRole());
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The user provider must be an instance of ApiKeyUserProvider
     */
    public function testAuthenticateTokenThrowsExceptionForIncorrectUserProvider()
    {
        $userProvider = $this->prophesize('Symfony\Component\Security\Core\User\UserProviderInterface');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->apiKeyAuthenticator->authenticateToken($token->reveal(), $userProvider->reveal(), 'key');
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidApiKeyException
     * @expectedExceptionMessage API key `apiKey` is invalid
     */
    public function testAuthenticateTokenThrowsExceptionForInvalidToken()
    {
        $userProvider = $this->prophesize('AppBundle\Security\ApiKeyUserProvider');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $token->getCredentials()->willReturn('apiKey');
        $this->apiKeyManager->getInfoFromApiKey('apiKey')->willThrow(new \Exception());

        $this->logger->error(Argument::cetera())->shouldBeCalled();
        $this->apiKeyAuthenticator->authenticateToken($token->reveal(), $userProvider->reveal(), 'key');
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidApiKeyException
     * @expectedExceptionMessage API key `apiKey` is invalid
     */
    public function testAuthenticateTokenThrowsExceptionForFakeToken()
    {
        $userProvider = $this->prophesize('AppBundle\Security\ApiKeyUserProvider');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user = $this->prophesize('AppBundle\Entity\User');

        $token->getCredentials()->willReturn('apiKey');
        $apiKeyInfo = new \stdClass();
        $apiKeyInfo->id = 1;
        $apiKeyInfo->token = 'userToken1';
        $this->apiKeyManager->getInfoFromApiKey('apiKey')->willReturn($apiKeyInfo);
        $userProvider->loadUserById(1)->willReturn($user);
        $user->getToken()->willReturn('userToken2');

        $this->logger->alert(Argument::cetera())->shouldBeCalled();
        $this->apiKeyAuthenticator->authenticateToken($token->reveal(), $userProvider->reveal(), 'key');
    }

    public function testSupportsTokenFalseIfNotPreAuthenticatedToken()
    {
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertFalse($this->apiKeyAuthenticator->supportsToken($token->reveal(), 'apiKey'));
    }

    public function testSupportsTokenFalseIfNotMatchingProviderKey()
    {
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken');
        $token->getProviderKey('apiKey1');

        $this->assertFalse($this->apiKeyAuthenticator->supportsToken($token->reveal(), 'apiKey2'));
    }

    public function testSupportsToken()
    {
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken');
        $token->getProviderKey('apiKey');

        $this->assertFalse($this->apiKeyAuthenticator->supportsToken($token->reveal(), 'apiKey'));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testOnAuthenticationFailureThrowsExceptionBecauseItIsNotImplemented()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $exception = $this->prophesize('Symfony\Component\Security\Core\Exception\AuthenticationException');

        $this->apiKeyAuthenticator->onAuthenticationFailure($request->reveal(), $exception->reveal());
    }
}
