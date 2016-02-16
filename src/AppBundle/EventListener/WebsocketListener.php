<?php

namespace AppBundle\EventListener;

use AppBundle\Battle\PlayerManager;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WebsocketListener
{
    /**
     * @var PlayerManager
     */
    protected $playerManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param PlayerManager $playerManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(PlayerManager $playerManager, TokenStorageInterface $tokenStorage)
    {
        $this->playerManager = $playerManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // Player Manager to get user from token
        $this->playerManager->setLoggedUser(null);
        // Token Storage to set new token
        $this->tokenStorage->setToken(null);
    }
}
