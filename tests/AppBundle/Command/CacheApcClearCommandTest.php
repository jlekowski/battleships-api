<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\CacheApcClearCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CacheApcClearCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new CacheApcClearCommand());

        $command = $application->find('cache:apc:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $this->assertEquals("OK\n", $commandTester->getDisplay());
    }
}
