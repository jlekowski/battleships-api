<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Game;
use AppBundle\Entity\User;
use AppBundle\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PlayerManager
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var User
     */
    protected $loggedUser;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Game $game
     * @return int
     * @throws \RuntimeException
     */
    public function findPlayerNumberForGame(Game $game)
    {
        $loggedUserId = $this->getLoggedUser()->getId();
        $isUser1 = $loggedUserId === $game->getUserId1();
        if (!$isUser1 && $game->getUserId2() && $loggedUserId !== $game->getUserId2()) {
            throw new \RuntimeException('Game belongs to other users');
        }

        return $isUser1 ? 1 : 2;
    }

    /**
     * @return User|null
     * @throws UserNotFoundException
     */
    public function getLoggedUser()
    {
        if (!$this->loggedUser) {
            $token = $this->tokenStorage->getToken();
            if (!$token) {
                throw new UserNotFoundException('User has not been authenticated yet');
            }
            $this->loggedUser = $token->getUser();
        }

        return $this->loggedUser;
    }

    /**
     * @param User $loggedUser
     * @return $this
     */
    public function setLoggedUser(User $loggedUser = null)
    {
        $this->loggedUser = $loggedUser;

        return $this;
    }
}
