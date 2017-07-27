<?php

namespace Tests\AppBundle\Command;

use AppBundle\Cache\OpcacheClearer;
use AppBundle\Command\CacheOpcacheClearCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CacheOpcacheClearCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $opcacheClearer = $this->prophesize(OpcacheClearer::class);
        $opcacheClearer->clear()->shouldBeCalled();

        $application = new Application();
        $application->add(new CacheOpcacheClearCommand($opcacheClearer->reveal()));

        $command = $application->find('cache:opcache:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $this->assertEquals("OK\n", $commandTester->getDisplay());
    }
}
