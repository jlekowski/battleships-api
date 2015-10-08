<?php

namespace AppBundle\EventListener;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
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
     * BattleManager object can't be injected during class instantiation because of circular reference
     *
     * @return BattleManager
     */
    public function getBattleManager()
    {
        return $this->container->get('app.battle.battle_manager');
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
     * @param Event $event
     * @throws InvalidEventTypeException
     */
    private function handleEventCreate(Event $event)
    {
        switch ($event->getType()) {
            case Event::TYPE_CHAT:
            case Event::TYPE_SHOT:
            case Event::TYPE_START_GAME:
                break;

            case Event::TYPE_JOIN_GAME:
            case Event::TYPE_NAME_UPDATE:
                throw new UnexpectedEventTypeException($event->getType());

            default:
                throw new InvalidEventTypeException($event->getType());
        }
    }
}
