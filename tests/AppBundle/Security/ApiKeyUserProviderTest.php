<?php

namespace Tests\AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use AppBundle\Security\ApiKeyUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApiKeyUserProvider
     */
    protected $apiKeyUserProvider;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function setUp()
    {
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->apiKeyUserProvider = new ApiKeyUserProvider($this->userRepository->reveal());
    }

    public function testLoadUserById()
    {
        $user = $this->prophesize(User::class);
        $this->userRepository->find(1)->willReturn($user);

        $this->assertEquals($user->reveal(), $this->apiKeyUserProvider->loadUserById(1));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage User with id `1` not found
     */
    public function testLoadUserByIdThrowsExceptionWhenNoUserWithGivenId()
    {
        $this->userRepository->find(1)->willReturn(null);

        $this->apiKeyUserProvider->loadUserById(1);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameThrowsExceptionBecauseItIsNotImplemented()
    {
        $this->apiKeyUserProvider->loadUserByUsername('test');
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshUserThrowsExceptionBecauseItIsNotImplemented()
    {
        $user = $this->prophesize(UserInterface::class);

        $this->apiKeyUserProvider->refreshUser($user->reveal());
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->apiKeyUserProvider->supportsClass(User::class));
        $this->assertFalse($this->apiKeyUserProvider->supportsClass('AppBundle\Entity\Entity')); // non-existing class
        $this->assertFalse($this->apiKeyUserProvider->supportsClass(UserInterface::class));
    }
}
