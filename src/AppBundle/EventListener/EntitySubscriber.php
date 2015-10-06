<?php

namespace AppBundle\EventListener;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidShipsException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class EntitySubscriber implements EventSubscriber
{
    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var BattleManager
     */
    protected $battleManager;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage, BattleManager $battleManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->battleManager = $battleManager;
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
     * @param array $changes
     * @throws InvalidShipsException
     */
    private function handleGameChanges(array $changes)
    {
        foreach ($changes as $property => $diff) {
            switch ($property) {
                case 'player1Ships':
                case 'player2Ships':
                    $this->validateShips($diff[1]);
                    break;
            }
        }
    }

    /**
     * @param Event $event
     * @throws \Exception
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
                throw new \Exception('Incorrect event type', Codes::HTTP_BAD_REQUEST);

            default:
                throw new \Exception('No such event type', Codes::HTTP_BAD_REQUEST);
        }
    }

    private function checkGameUpdates(Game $game)
    {
//        $game = $this->gameRepository->find($id);
//        $game->setPlayer2Name('aaa');
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        echo "<pre>";
        var_dump($uow->getEntityChangeSet($game));
        exit;
    }
}
