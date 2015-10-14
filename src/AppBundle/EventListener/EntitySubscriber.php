<?php

namespace AppBundle\EventListener;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Exception\GameFlowException;
use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidEventTypeException;
use AppBundle\Exception\InvalidShipsException;
use AppBundle\Exception\UnexpectedEventTypeException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntitySubscriber implements EventSubscriber, ContainerAwareInterface
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
     * @var ContainerInterface
     */
    protected $container;

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
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $unitOfWork = $eventArgs->getEntityManager()->getUnitOfWork();
        $entity = $eventArgs->getEntity();
        $changes = $unitOfWork->getEntityChangeSet($entity);

        if ($entity instanceof Game) {
            $errors = $this->validator->validate($entity, null, ['playerShips']);
            echo "<pre>";
            var_dump($errors->count(), $errors);
            exit;
            //@todo maybe validate only selected groups (e.g. ships)
//            $this->handleGameUpdate($changes);
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @throws \Exception
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Event) {
            $errors = $this->validator->validate($entity);
            echo "<pre>";
            var_dump($errors->count());
            foreach ($errors as $error) {
                var_dump($error->getMessage(), $error->getCode(), $error->getInvalidValue());
            }
            exit;
            $this->handleEventCreate($entity);
        }
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

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param array $changes
     * @throws InvalidShipsException
     */
    private function handleGameUpdate(array $changes)
    {
        foreach ($changes as $property => $diff) {
            switch ($property) {
                case 'player1Ships':
                case 'player2Ships':
                    // @todo check here or somewhere is allowed to update ships (after starting the game)
                    $this->getBattleManager()->validateShips($diff[1]);
                    break;
            }
        }
    }

    /**
     * @todo here validate only allowed types to be inserted - type available from request check in GameVoter
     * @param Event $event
     * @throws InvalidEventTypeException
     * @throws GameFlowException
     * @throws InvalidCoordinatesException
     */
    private function handleEventCreate(Event $event)
    {
        $eventType = $event->getType();
        switch ($eventType) {
            case Event::TYPE_CHAT:
                break;

            case Event::TYPE_SHOT:
                // @todo maybe a different place + http code for the exception
                $this->getBattleManager()->validateShootingNow($event);
                break;

            case Event::TYPE_JOIN_GAME:
            case Event::TYPE_START_GAME:
                $this->getBattleManager()->validateAddingUniqueEvent($event);
                break;

            case Event::TYPE_NAME_UPDATE:
                throw new UnexpectedEventTypeException($eventType);

            default:
                throw new InvalidEventTypeException($eventType);
        }
    }

    /**
     * BattleManager object can't be injected during class instantiation because of circular reference
     *
     * @return BattleManager
     */
    private function getBattleManager()
    {
        return $this->container->get('app.battle.battle_manager');
    }
}
