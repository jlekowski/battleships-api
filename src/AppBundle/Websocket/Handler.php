<?php

namespace AppBundle\Websocket;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Handler implements MessageComponentInterface, ContainerAwareInterface
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
    public function onMessage(ConnectionInterface $from, $msg)
    {
//        $numRecv = count($this->clients) - 1;
//        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . PHP_EOL
//            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
//
//        foreach ($this->clients as $client) {
//            if ($from !== $client) {
//                // The sender is not the receiver, send to each client connected
//                $client->send($msg);
//            }
//        }

        $requestDetails = json_decode($msg, true);
        $request = Request::create(
            $requestDetails['url'],
            $requestDetails['method'],
            [],
            [],
            [],
            $requestDetails['headers'],
            $requestDetails['data']
        );
        $httpKernel = $this->container->get('http_kernel');
        $response = $httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST);
        if ($response->isSuccessful()) {

        }

        $from->send($response->getContent());
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
