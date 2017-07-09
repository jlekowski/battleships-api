<?php

namespace AppBundle\Command;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This does not make much sense, but it was fun to write it :)
 * To check how secure JWT secret is, this website works quite well https://howsecureismypassword.net.
 * If secret is like 'test', it takes around 15 minutes to find it, but brute forcing anything longer is pointless.
 * Having a properly long secret for JWT is secure enough. I NSA wants to break my API, they will do it anyway :)
 */
class UserTokenBreakCommand extends Command
{
    /**
     * @var array
     */
    protected $charIds = [];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('user:token:break')
            ->setDescription('Breaks JWT')
            ->addArgument('jwt', InputArgument::REQUIRED)
            ->addArgument('start', InputArgument::OPTIONAL, 'String it starts searching from', '')
            ->addArgument('timeout', InputArgument::OPTIONAL, 'How long should it try to find secret (in seconds)', 1200)
            ->addArgument('step', InputArgument::OPTIONAL, 'How many times it should try to find secret before checking timeout and marking progress', 100000)
        ;

        // 0-9, A-Z, a-z (http://www.asciitable.com)
        $this->charIds = array_merge(range(48, 57), range(65, 90), range(97, 122));
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jwt = $input->getArgument('jwt');
        $secret = $input->getArgument('start');
        $timeout = $input->getArgument('timeout');
        $step = $input->getArgument('step');

        $found = false;
        $start = microtime(true);
        while (microtime(true) - $start < $timeout) {
            for ($i = 0; $i < $step; $i++) {
                try {
                    JWT::decode($jwt, $secret, ['HS256']);
                    $found = true;
                    break 2;
                } catch (\Exception $e) {
                    $secret = $this->findNext($secret);
                }
            }
            $output->write(sprintf('%s, ', $secret));
        }

        $took = number_format(microtime(true) - $start, 2);
        $memoryUsed = number_format(memory_get_peak_usage() / (1024 * 1024), 2) . ' MB';
        if ($found) {
            $output->writeln(sprintf('<info>Secret found:</info> %s (took: %s, memory: %s)', $secret, $took, $memoryUsed));
        } else {
            $output->writeln(sprintf('<error>Secret not found:</error> finished on %s (took: %s, memory: %s)', $secret, $took, $memoryUsed));
        }
    }

    /**
     * @param string $txt
     * @return string
     */
    protected function findNext($txt = '')
    {
        if ($txt === '') {
            return chr($this->charIds[0]);
        }

        $baseChr = substr($txt, 0, -1);
        $lastChr = substr($txt, -1);

        $key = array_search(ord($lastChr), $this->charIds);
        if (isset($this->charIds[$key + 1])) {
            $next = $baseChr . chr($this->charIds[$key + 1]);
        } else {
            // find down the string non 'z'
            $strLen = strlen($txt);
            for ($i = 1; $i <= $strLen; $i++) {
                $char = substr($txt, -($i + 1), -$i);
                if (!$char || $char !== $lastChr) {
                    break;
                }
            }

            $next = $this->findNext(substr($txt, 0, $strLen - $i)) . str_repeat(chr($this->charIds[0]), $i);
        }

        return $next;
    }
}
