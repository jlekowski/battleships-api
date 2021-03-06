<?php

namespace Tests\AppBundle\Command;

use AppBundle\Cache\ApcClearer;
use AppBundle\Command\CacheApcClearCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CacheApcClearCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $apcClearer = $this->prophesize(ApcClearer::class);
        $apcClearer->clear()->shouldBeCalled();

        $application = new Application();
        $application->add(new CacheApcClearCommand($apcClearer->reveal()));

        $command = $application->find('cache:apc:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName()
        ]);

        $this->assertEquals("OK\n", $commandTester->getDisplay());
    }
}
