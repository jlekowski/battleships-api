<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use AppBundle\Exception\UserNotFoundException;
use AppBundle\Security\ApiKeyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserTokenGenerateCommand extends Command
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var ApiKeyManager
     */
    protected $apiKeyManager;

    /**
     * @param UserRepository $userRepository
     * @param ApiKeyManager $apiKeyManager
     * @param string $name
     */
    public function __construct(UserRepository $userRepository, ApiKeyManager $apiKeyManager, $name = null)
    {
        parent::__construct($name);
        $this->userRepository = $userRepository;
        $this->apiKeyManager = $apiKeyManager;
    }


    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('user:token:generate')
            ->setDescription('Generates JWT for user')
            ->addArgument('user_id', InputArgument::REQUIRED)
            ->addOption(
                'new',
                null,
                InputOption::VALUE_NONE,
                'If set, token for user would be regenerated, JWT would invalidate, cache would need to be invalidated'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('user_id');

        /** @var User $user */
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException(sprintf('User `%s` does not exist', $userId));
        }

        if ($input->getOption('new')) {
            throw new \InvalidArgumentException('Parameter `new` is not ready yet - cache invalidation required');
        }

        $apiKey = $this->apiKeyManager->generateApiKeyForUser($user);

        $output->writeln(sprintf('<info>New API Key:</info> %s', $apiKey));
    }
}
