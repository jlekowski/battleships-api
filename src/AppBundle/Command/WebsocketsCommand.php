<?php

namespace AppBundle\Command;

use AppBundle\Websocket\Server;
use Ratchet\App;
use Ratchet\Server\IoServer;
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
//        $server = new App('localhost', 8080, '192.168.1.234');
        /** @var Server $wsServer */
        $wsServer = $this->getContainer()->get('app.websocket.server');
        $server = IoServer::factory($wsServer, 8080);
        $output->writeln('<info>Running</info>');
//        $server->route('/', $wsServer);
        $server->run();
    }
}
