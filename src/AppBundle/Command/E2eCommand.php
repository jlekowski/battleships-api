<?php

namespace AppBundle\Command;

use AppBundle\Entity\Event;
use BattleshipsApiComponent\Client\Request\ApiRequest;
use BattleshipsApiComponent\Client\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class E2eCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('test:e2e')
            ->setDescription('Runs E2E test')
            ->addArgument('url', InputArgument::OPTIONAL, 'API url', 'http://battleships-api.dev.lekowski.pl:6081/v1')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $baseUrl = $input->getArgument('url');
        $apiRequest = new ApiRequest($baseUrl);

        $user = $apiRequest->createUser('New Player');
        $apiRequest->setAuthToken($user->apiKey);
        $output->writeln(sprintf('User Id: %s', $user->id));
        $output->writeln(sprintf('User API Key: %s', $user->apiKey));

        $gameId = $apiRequest->createGame();
        $output->writeln(sprintf('Game Id: %s', $gameId));

        $response = $apiRequest->getGame($gameId);
        $output->writeln('Game for player');
        $this->outputResponse($output, $response);

        $apiRequest->updateName($user->id, 'New Player 132');
        $output->writeln('User Patched (name)');

        $response = $apiRequest->getUser($user->id);
        $output->writeln('User details');
        $this->outputResponse($output, $response);

        $output->writeln('Game to be Patched (player ships)');
        $response = $apiRequest->updateGame(
            $gameId,
            ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        );
        $output->writeln('Game Patched (player ships)');
        $this->outputResponse($output, $response);

        $output->writeln('Event to be Posted (chat)');
        $response = $apiRequest->createEvent($gameId, Event::TYPE_CHAT, 'Test chat');
        $output->writeln('Chat added');
        $this->outputResponse($output, $response);

        $other = $apiRequest->createUser('New Other');
        $apiRequest->setAuthToken($other->apiKey);
        $output->writeln(sprintf('Other Id: %s', $other->id));
        $output->writeln(sprintf('Other API Key: %s', $other->apiKey));

        $response = $apiRequest->getGamesAvailable();
        $output->writeln('Available games for other');
        $this->outputResponse($output, $response);

        $output->writeln('Game to be Patched (other join)');
        $response = $apiRequest->updateGame($gameId, [], true);
        $output->writeln('Game Patched (other join)');
        $this->outputResponse($output, $response);

        $response = $apiRequest->getGame($gameId);
        $output->writeln('Game for other');
        $this->outputResponse($output, $response);

        $output->writeln('Game to be Patched (other ships)');
        $response = $apiRequest->updateGame(
            $gameId,
            ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']
        );
        $output->writeln('Game Patched (other ships)');
        $this->outputResponse($output, $response);

        $apiRequest->setAuthToken($user->apiKey);
        $response = $apiRequest->createEvent($gameId, Event::TYPE_SHOT, 'A10');
        $output->writeln('Shot added');
        $this->outputResponse($output, $response);

        $response = $apiRequest->getEvents($gameId, 0);
        $this->outputResponse($output, $response);

        $response = $apiRequest->getEvents($gameId, 0, Event::TYPE_SHOT);
        $this->outputResponse($output, $response);

        $response = $apiRequest->getGame($gameId);
        $output->writeln('Game for player');
        $this->outputResponse($output, $response);

        $output->writeln(sprintf('<info>Finished in %s</info>',  microtime(true) - $start));
    }

    /**
     * @param OutputInterface $output
     * @param ApiResponse $response
     */
    private function outputResponse(OutputInterface $output, ApiResponse $response)
    {
        $output->writeln(sprintf('<comment>%s</comment>', print_r($response->getJson(), true)), OutputInterface::VERBOSITY_VERBOSE);
    }
}
