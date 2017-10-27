<?php

namespace AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ApiKeyUserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param int $userId
     * @return User
     * @throws AuthenticationException
     */
    public function loadUserById($userId)
    {
        /** @var User|null $user */
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new AuthenticationException(sprintf('User with id `%s` not found', $userId));
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function loadUserByUsername($username)
    {
        throw new UsernameNotFoundException();
    }

    /**
     * @inheritdoc
     */
    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    /**
     * @inheritdoc
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
