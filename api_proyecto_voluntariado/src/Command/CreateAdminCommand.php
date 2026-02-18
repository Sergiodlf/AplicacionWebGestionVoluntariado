<?php

namespace App\Command;

use App\Entity\Administrador;
use App\Service\FirebaseServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
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
        private FirebaseServiceInterface $firebaseService
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

        // 1. Create User in Firebase (Centralized)
        $io->section('Creating Firebase User...');
        try {
            $uid = $this->firebaseService->createUser($email, $password, $nombre);
            $this->firebaseService->setUserRole($uid, 'admin');
            $io->success('Firebase user and claims processed (rol: admin).');

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
