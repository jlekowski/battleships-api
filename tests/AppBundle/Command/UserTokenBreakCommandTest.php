<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\UserTokenBreakCommand;
use Firebase\JWT\JWT;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserTokenBreakCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UserTokenBreakCommand
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    public function setUp()
    {
        $application = new Application();
        $application->add(new UserTokenBreakCommand());

        $this->command = $application->find('user:token:break');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSecretFound()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $userRepository = $this->prophesize('AppBundle\Entity\UserRepository');
        $apiKeyManager = $this->prophesize('AppBundle\Security\ApiKeyManager');
        $user = $this->prophesize('AppBundle\Entity\User');

        $container->get('app.entity.user_repository')->willReturn($userRepository);
        $container->get('app.security.api_key_manager')->willReturn($apiKeyManager);
        $apiKeyManager->generateApiKeyForUser($user)->willReturn('apiKey');
        $userRepository->find(1)->willReturn($user);

        $jwt = JWT::encode([], 2);
        $this->command->setContainer($container->reveal());
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'jwt' => $jwt,
            'timeout' => 2
        ]);

        $this->assertStringMatchesFormat("Secret found: 2 (took: %s, memory: %s)\n", $this->commandTester->getDisplay());
    }

    public function testExecuteSecretNotFound()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $userRepository = $this->prophesize('AppBundle\Entity\UserRepository');
        $apiKeyManager = $this->prophesize('AppBundle\Security\ApiKeyManager');
        $user = $this->prophesize('AppBundle\Entity\User');

        $container->get('app.entity.user_repository')->willReturn($userRepository);
        $container->get('app.security.api_key_manager')->willReturn($apiKeyManager);
        $apiKeyManager->generateApiKeyForUser($user)->willReturn('apiKey');
        $userRepository->find(1)->willReturn($user);

        $jwt = JWT::encode([], 'zzzzz');
        $this->command->setContainer($container->reveal());
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'jwt' => $jwt,
            'timeout' => 0.1,
            'step' => 1000
        ]);

        $this->assertStringMatchesFormat("%sSecret not found: finished on %s (took: %s, memory: %s)\n", $this->commandTester->getDisplay());
    }
}
