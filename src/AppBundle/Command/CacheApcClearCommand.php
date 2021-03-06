<?php

namespace AppBundle\Command;

use AppBundle\Cache\ApcClearer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheApcClearCommand extends Command
{
    /**
     * @var ApcClearer
     */
    protected $apcClearer;

    /**
     * @param ApcClearer $opcacheClearer
     * @param string $name
     */
    public function __construct(ApcClearer $opcacheClearer, $name = null)
    {
        parent::__construct($name);
        $this->apcClearer = $opcacheClearer;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('cache:apc:clear')
            ->setDescription('Clears APC cache')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->apcClearer->clear();

        $output->writeln('<info>OK</info>');
    }
}
