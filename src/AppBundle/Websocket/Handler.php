<?php

namespace AppBundle\Websocket;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Handler implements WampServerInterface, ContainerAwareInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * @inheritdoc
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * @inheritdoc
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * @inheritdoc
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    /**
     * @inheritdoc
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        echo __FUNCTION__ . PHP_EOL;
        printf('conn: %s, id: %s, topic: %s, params: %s', $conn->resourceId, $id, $topic, print_r($params, true));

//        $eventManager = $this->container->get('doctrine.orm.default_entity_manager')->getEventManager();
//        $eventManager->dispatchEvent('beforeApiRequest', new SomeEvent($topic));

        $entityWebsocketSubscriber = $this->container->get('app.event_subscriber.websocket_entity');
        $entityWebsocketSubscriber->setBroadcastDetails($topic, [$conn->WAMP->sessionId]);

        $request = Request::create(
            $params['url'],
            $params['method'],
            [],
            [],
            [],
            $params['headers'],
            json_encode($params['data'])
        );
        $httpKernel = $this->container->get('http_kernel');
        $response = $httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST);
        printf('response: %s', $response->getContent());

        if ($conn instanceof WampConnection) {
            $wsResponse = [
                'headers' => $response->headers->all(),
                'content' => json_decode($response->getContent())
            ];
            $conn->callResult($id, $wsResponse);
        }
    }

    /**
     * @inheritdoc
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        echo __FUNCTION__ . PHP_EOL;
        printf('conn: %s, topic: %s', $conn->resourceId, $topic);
        if ($topic instanceof Topic) {
            printf("\ncount: %d\n", $topic->count());
        }
    }

    /**
     * @inheritdoc
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        echo __FUNCTION__ . PHP_EOL;
        printf('conn: %s, topic: %s', $conn->resourceId, $topic);
    }

    /**
     * @inheritdoc
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        echo __FUNCTION__ . PHP_EOL;
        printf('conn: %s, topic: %s, event: %s, exclude: %s, eligible: %s', $conn->resourceId, $topic, print_r($event, true), print_r($exclude, true), print_r($eligible, true));
        if ($topic instanceof Topic) {
            $topic->broadcast('published (broadcasted)');
        }
    }


    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
