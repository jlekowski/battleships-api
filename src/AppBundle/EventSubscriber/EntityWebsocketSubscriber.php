<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Websocket\SomeEvent;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EntityWebsocketSubscriber implements EventSubscriber
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var array
     */
    protected $broadcastDetails = [];

    /**
     * @var array
     */
    protected $entitiesOnFlush = [];

    /**
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getSubscribedEvents()
    {
        return [
            'beforeApiRequest',
            Events::onFlush,
            Events::postFlush
        ];
    }

    /**
     * @param Topic $topic
     * @param array $exclude
     * @param array $eligible
     * @return $this
     */
    public function setBroadcastDetails(Topic $topic, array $exclude = [], array $eligible = [])
    {
        $this->broadcastDetails = ['topic' => $topic, 'exclude' => $exclude, 'eligible' => $eligible];

        return $this;
    }

    /**
     * @param SomeEvent $event
     */
    public function beforeApiRequest(SomeEvent $event)
    {
        $this->logger->info(sprintf('GOT SOME EVENT: %s', $event));
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->broadcastDetails) {
            return;
        }

        $this->entitiesOnFlush = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        if (!$this->broadcastDetails) {
            return;
        }

        /** @var Topic $topic */
        $topic = $this->broadcastDetails['topic'];
        foreach ($this->entitiesOnFlush as $entity) {
            $entityObj = json_decode($this->serializer->serialize($entity, 'json'));
            $topic->broadcast($entityObj, $this->broadcastDetails['exclude'], $this->broadcastDetails['eligible']);

//            if ($entity instanceof Game) {
//                // update available games
//            }
//            if ($entity instanceof Event) {
//                // send game event info
//                // if name_update send to all user's active games
//            }
        }

        $this->entitiesOnFlush = [];
        $this->broadcastDetails = [];
    }
}
