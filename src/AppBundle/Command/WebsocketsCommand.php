<?php

namespace AppBundle\Command;

use AppBundle\Websocket\Handler;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
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
        $server = IoServer::factory(new HttpServer(new WsServer($wsHandler)), 8080);
        $output->writeln('<info>Initialised</info>');
        $server->run();
    }
}
