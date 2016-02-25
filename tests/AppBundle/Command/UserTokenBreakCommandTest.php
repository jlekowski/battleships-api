<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\UserTokenBreakCommand;
use Firebase\JWT\JWT;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserTokenBreakCommandTest extends \PHPUnit_Framework_TestCase
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

    // step is hardcoded and 100000 means that the test would run too long
    public function ignoreTestExecuteSecretNotFound()
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
            'timeout' => 1
        ]);

        $this->assertStringMatchesFormat("Secret not found: finished on %s (took: %s, memory: %s)\n", $this->commandTester->getDisplay());
    }
}
