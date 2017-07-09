<?php

namespace AppBundle\EventSubscriber;

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
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param TokenStorage $tokenStorage
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     */
    public function __construct(TokenStorage $tokenStorage, ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
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
            /** @var PreAuthenticatedToken $token */
            $token = $this->tokenStorage->getToken();
            if (!$token) {
                throw new UserNotFoundException('User has not been authenticated yet');
            }

            $entity->setLoggedUser($token->getUser());
        }
    }
}
