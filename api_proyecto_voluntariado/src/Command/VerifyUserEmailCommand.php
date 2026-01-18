<?php

namespace App\Command;

use Kreait\Firebase\Contract\Auth;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:verify-email',
    description: 'Manually verifies a Firebase user email.',
)]
class VerifyUserEmailCommand extends Command
{
    public function __construct(
        private Auth $firebaseAuth
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address to verify')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        try {
            $user = $this->firebaseAuth->getUserByEmail($email);
            
            $this->firebaseAuth->updateUser($user->uid, [
                'emailVerified' => true
            ]);

            $io->success(sprintf('User "%s" (UID: %s) has been verified successfully.', $email, $user->uid));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error(sprintf('Error verifying user: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
