<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Entity\UserRepository;
use AppBundle\Exception\UserNotFoundException;
use AppBundle\Security\ApiKeyManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserTokenGenerateCommand extends ContainerAwareCommand
{
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
        $user = $this->getUserRepository()->find($userId);
        if (!$userId) {
            throw new UserNotFoundException('User `%s` does not exist', $userId);
        }

        if ($input->getOption('new')) {
            throw new \InvalidArgumentException('Parameter `new` is not ready yet - cache invalidation required');
        }

        $apiKey = $this->getApiKeyManager()->generateApiKeyForUser($user);

        $output->writeln(sprintf('<info>New API Key:</info> %s', $apiKey));
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getContainer()->get('app.entity.user_repository');
    }

    /**
     * @return ApiKeyManager
     */
    protected function getApiKeyManager()
    {
        return $this->getContainer()->get('app.security.api_key_manager');
    }
}
