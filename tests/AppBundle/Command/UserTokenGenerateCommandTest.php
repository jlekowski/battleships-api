<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\UserTokenGenerateCommand;
use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use AppBundle\Security\ApiKeyManager;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserTokenGenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UserTokenGenerateCommand
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    /**
     * @var UserRepository|ObjectProphecy
     */
    protected $userRepository;

    /**
     * @var ApiKeyManager|ObjectProphecy
     */
    protected $apiKeyManager;

    public function setUp()
    {
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->apiKeyManager = $this->prophesize(ApiKeyManager::class);

        $application = new Application();
        $application->add(new UserTokenGenerateCommand($this->userRepository->reveal(), $this->apiKeyManager->reveal()));

        $this->command = $application->find('user:token:generate');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $user = $this->prophesize(User::class);
        $this->userRepository->find(1)->willReturn($user);
        $this->apiKeyManager->generateApiKeyForUser($user)->willReturn('apiKey');

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
        $this->userRepository->find(1)->willReturn(null);

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
        $user = $this->prophesize(User::class);
        $this->userRepository->find(1)->willReturn($user);

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'user_id' => 1,
            '--new' => true,
        ]);
    }
}
