<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\UserTokenGenerateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserTokenGenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserTokenGenerateCommand
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    public function setUp()
    {
        $application = new Application();
        $application->add(new UserTokenGenerateCommand());

        $this->command = $application->find('user:token:generate');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $userRepository = $this->prophesize('AppBundle\Entity\UserRepository');
        $apiKeyManager = $this->prophesize('AppBundle\Security\ApiKeyManager');
        $user = $this->prophesize('AppBundle\Entity\User');

        $container->get('app.entity.user_repository')->willReturn($userRepository);
        $container->get('app.security.api_key_manager')->willReturn($apiKeyManager);
        $apiKeyManager->generateApiKeyForUser($user)->willReturn('apiKey');
        $userRepository->find(1)->willReturn($user);

        $this->command->setContainer($container->reveal());
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'user_id' => 1
        ]);

        $this->assertEquals("New API Key: apiKey\n", $this->commandTester->getDisplay());
    }

    /**
     * @expectedException \AppBundle\Exception\UserNotFoundException
     * @expectedExceptionMessage User `1` does not exist
     */
    public function testExecuteThrowsExceptionWhenNoUserFound()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $userRepository = $this->prophesize('AppBundle\Entity\UserRepository');

        $container->get('app.entity.user_repository')->willReturn($userRepository);
        $userRepository->find(1)->willReturn(null);

        $this->command->setContainer($container->reveal());
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'user_id' => 1
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Parameter `new` is not ready yet - cache invalidation required
     */
    public function testExecuteThrowsExceptionWhenUsingNewOptions()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $userRepository = $this->prophesize('AppBundle\Entity\UserRepository');
        $user = $this->prophesize('AppBundle\Entity\User');

        $container->get('app.entity.user_repository')->willReturn($userRepository);
        $userRepository->find(1)->willReturn($user);

        $this->command->setContainer($container->reveal());
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'user_id' => 1,
            '--new' => true,
        ]);
    }
}
