<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Game;
use AppBundle\Exception\DuplicatedEventTypeException;
use AppBundle\Exception\GameFlowException;
use AppBundle\Exception\InvalidShipsException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntitySubscriber implements EventSubscriber
{
    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param TokenStorage $tokenStorage
     * @param ValidatorInterface $validator
     */
    public function __construct(TokenStorage $tokenStorage, ValidatorInterface $validator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
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
     * @throws \Exception
     */
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Game) {
            // there may be no user yet (loading entity during authorisation)
            $entity->setTokenStorage($this->tokenStorage);
        }
    }
}
