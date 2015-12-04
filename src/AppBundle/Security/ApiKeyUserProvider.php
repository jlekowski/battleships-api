<?php

namespace AppBundle\Security;

use AppBundle\Entity\GameRepository;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ApiKeyUserProvider implements UserProviderInterface
{
    /**
     * @var GameRepository
     */
    protected $gameRepository;

    /**
     * @param GameRepository $gameRepository
     */
    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    /**
     * @param string $apiKey
     * @return User
     * @throws AuthenticationException
     */
    public function getUserForApiKey($apiKey)
    {
        if (!preg_match('/^user:(\d+)$/', $apiKey, $matches)) {
            throw new AuthenticationException(sprintf('API key `%s` is invalid', $apiKey));
        }

        $userId = (int)$matches[1];

        return new User($userId);
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
        return 'AppBundle\Entity\User' === $class;
    }
}
