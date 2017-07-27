<?php

namespace AppBundle\Command;

use AppBundle\Cache\OpcacheClearer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheOpcacheClearCommand extends Command
{
    /**
     * @var OpcacheClearer
     */
    protected $opcacheClearer;

    /**
     * @param OpcacheClearer $opcacheClearer
     * @param string $name
     */
    public function __construct(OpcacheClearer $opcacheClearer, $name = null)
    {
        parent::__construct($name);
        $this->opcacheClearer = $opcacheClearer;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('cache:opcache:clear')
            ->setDescription('Clears OPcache cache')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->opcacheClearer->clear();

        $output->writeln('<info>OK</info>');
    }
}
