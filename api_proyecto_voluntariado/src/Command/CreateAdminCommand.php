<?php

namespace App\Command;

use App\Entity\Administrador;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user in Database and Firebase',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Auth $firebaseAuth
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the admin')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the admin')
            ->addArgument('nombre', InputArgument::REQUIRED, 'The name of the admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $nombre = $input->getArgument('nombre');

        // 1. Create User in Firebase
        $io->section('Creating Firebase User...');
        try {
            // Check if user exists
            try {
                $firebaseUser = $this->firebaseAuth->getUserByEmail($email);
                $io->note('User already exists in Firebase. Updating claims...');
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                // Create user
                $userProperties = [
                    'email' => $email,
                    'emailVerified' => true,
                    'password' => $password,
                    'displayName' => $nombre,
                    'disabled' => false,
                ];
                $firebaseUser = $this->firebaseAuth->createUser($userProperties);
                $io->success('Firebase user created: ' . $firebaseUser->uid);
            }

            // Set Admin Claims
            $this->firebaseAuth->setCustomUserClaims($firebaseUser->uid, ['rol' => 'admin', 'admin' => true]);
            $io->success('Firebase claims set (rol: admin).');

        } catch (\Exception $e) {
            $io->error('Firebase Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Create User in Database
        $io->section('Creating Database Entity...');
        
        $repo = $this->entityManager->getRepository(Administrador::class);
        $existingAdmin = $repo->findOneBy(['email' => $email]);

        if ($existingAdmin) {
            $io->warning('Admin already exists in Database. Updating password...');
            $admin = $existingAdmin;
        } else {
            $admin = new Administrador();
            $admin->setEmail($email);
        }

        $admin->setNombre($nombre);
        $admin->setRoles(['ROLE_ADMIN']); // Explicitly set role



        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user "%s" created successfully.', $email));

        return Command::SUCCESS;
    }
}
