<?php

namespace AppBundle\Command;

use AppBundle\Websocket\Handler;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('server:websockets')
            ->setDescription('Starts websockets server')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Handler $wsHandler */
        $wsHandler = $this->getContainer()->get('app.websocket.handler');
//        $server = IoServer::factory(new HttpServer(new WsServer($wsHandler)), 8080);
        $server = IoServer::factory(new HttpServer(new WsServer(new WampServer($wsHandler))), 8080);
        $output->writeln('<info>Initialised</info>');
        $server->run();

//
//        $loop   = React\EventLoop\Factory::create();
//        $pusher = new MyApp\Pusher;
//
//        // Listen for the web server to make a ZeroMQ push after an ajax request
//        $context = new React\ZMQ\Context($loop);
//        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
//        $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
//        $pull->on('message', array($pusher, 'onBlogEntry'));
//
//        // Set up our WebSocket server for clients wanting real-time updates
//        $webSock = new React\Socket\Server($loop);
//        $webSock->listen(8080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
//        $webServer = new Ratchet\Server\IoServer(
//            new Ratchet\Http\HttpServer(
//                new Ratchet\WebSocket\WsServer(
//                    new Ratchet\Wamp\WampServer(
//                        $pusher
//                    )
//                )
//            ),
//            $webSock
//        );
//
//        $loop->run();
    }
}
