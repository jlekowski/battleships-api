<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Battle\PlayerManager;
use AppBundle\Entity\Game;
use AppBundle\Exception\DuplicatedEventTypeException;
use AppBundle\Exception\GameFlowException;
use AppBundle\Exception\InvalidShipsException;
use AppBundle\Exception\UserNotFoundException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntitySubscriber implements EventSubscriber
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var PlayerManager
     */
    protected $playerManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ValidatorInterface $validator
     * @param PlayerManager $playerManager
     * @param LoggerInterface $logger
     */
    public function __construct(TokenStorageInterface $tokenStorage, ValidatorInterface $validator, PlayerManager $playerManager, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
        $this->playerManager = $playerManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::prePersist,
            Events::postLoad
        ];
    }

    /**
     * @param PreUpdateEventArgs $eventArgs
     * @throws InvalidShipsException
     * @throws GameFlowException
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->validator->validate($eventArgs->getEntity(), null, ['update']);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @throws DuplicatedEventTypeException
     * @throws GameFlowException
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->validator->validate($eventArgs->getEntity());
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @throws UserNotFoundException
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof LoggerAwareInterface) {
            $entity->setLogger($this->logger);
        }

        if ($entity instanceof Game) {
            $entity->setPlayerManager($this->playerManager);
        }
    }
}
