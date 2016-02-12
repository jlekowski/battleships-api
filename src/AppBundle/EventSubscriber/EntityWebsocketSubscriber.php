<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

class EntityWebsocketSubscriber implements EventSubscriber
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $entitiesOnFlush = [];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postFlush
        ];
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->entitiesOnFlush = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        foreach ($this->entitiesOnFlush as $entity) {
            $this->logger->info('id', [$entity->getId(), get_class($entity)]);
            if ($entity instanceof Game) {
                // update available games
            }
            if ($entity instanceof Event) {
                // send game event info
                // if name_update send to all user's active games
            }
        }

        $this->entitiesOnFlush = [];
    }
}
