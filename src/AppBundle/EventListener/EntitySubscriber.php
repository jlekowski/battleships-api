<?php

namespace AppBundle\EventListener;

use AppBundle\Battle\BattleManager;
use AppBundle\Battle\CoordsInfo;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\DuplicatedEventTypeException;
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

class EntitySubscriber implements EventSubscriber, ContainerAwareInterface
{
    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
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
            $this->handleGameChanges($changes);
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
    private function handleGameChanges(array $changes)
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
     */
    private function handleEventCreate(Event $event)
    {
        $eventType = $event->getType();
        switch ($eventType) {
            case Event::TYPE_CHAT:
                break;

            case Event::TYPE_SHOT:
                // @todo maybe a better validation?
                new CoordsInfo($event->getValue());
                break;

            case Event::TYPE_JOIN_GAME:
            case Event::TYPE_START_GAME:
                if ($this->hasPlayerAlreadyCreatedEvent($event->getGame(), $eventType)) {
                    throw new DuplicatedEventTypeException($eventType);
                }
                break;

            case Event::TYPE_NAME_UPDATE:
                throw new UnexpectedEventTypeException($eventType);

            default:
                throw new InvalidEventTypeException($eventType);
        }
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @return bool
     */
    private function hasPlayerAlreadyCreatedEvent(Game $game, $eventType)
    {
        $events = $this->getEventRepository()->findForGameByTypeAndPlayer(
            $game,
            $eventType,
            $game->getPlayerNumber()
        );

        return !$events->isEmpty();
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

    /**
     * EventRepository object can't be injected during class instantiation because of circular reference
     *
     * @return EventRepository
     */
    private function getEventRepository()
    {
        return $this->container->get('app.entity.event_repository');
    }
}
