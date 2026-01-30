<?php

namespace App\Command;

use App\Entity\Voluntario;
use App\Entity\Organizacion;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates test users (Volunteer & Organization) with verified emails.',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private Auth $firebaseAuth,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Creating Test Users...');
        
        try {
            $this->createTestVolunteer($output);
            $this->createTestOrganization($output);
            $output->writeln('Test users created successfully!');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('Error: ' . $e->getMessage());
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function createTestVolunteer(OutputInterface $output): void
    {
        $email = 'voluntario_test@curso.com';
        $password = '123456';
        $dni = '11111111A';

        $output->writeln("--- Processing Volunteer: $email ---");

        // A. Firebase
        try {
            try {
                $user = $this->firebaseAuth->getUserByEmail($email);
                $this->firebaseAuth->updateUser($user->uid, [
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => 'Voluntario Test'
                ]);
                $uid = $user->uid;
                $output->writeln("Firebase: User updated.");
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                $user = $this->firebaseAuth->createUser([
                    'email' => $email,
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => 'Voluntario Test'
                ]);
                $uid = $user->uid;
                $output->writeln("Firebase: User created.");
            }
            $this->firebaseAuth->setCustomUserClaims($uid, ['rol' => 'voluntario']);
        } catch (\Throwable $e) {
            $output->writeln("Firebase Error: " . $e->getMessage());
            // Continue to DB...
        }

        // B. Database
        $voluntario = $this->em->getRepository(Voluntario::class)->find($dni);
        if (!$voluntario) {
            $voluntario = new Voluntario();
            $voluntario->setDni($dni);
        }

        $voluntario->setNombre('Voluntario');
        $voluntario->setApellido1('Test');
        $voluntario->setApellido2('Pruebas');
        $voluntario->setCorreo($email); // Correct method from inspection
        $voluntario->setPassword($this->passwordHasher->hashPassword($voluntario, $password));
        $voluntario->setZona('Pamplona');
        $voluntario->setFechaNacimiento(new \DateTime('1990-01-01'));
        $voluntario->setExperiencia('Sin experiencia');
        $voluntario->setCoche(true);
        $voluntario->setEstadoVoluntario('LIBRE');
        // $voluntario->setRoles() is removed as it doesn't exist.

        $this->em->persist($voluntario);
        $this->em->flush();
        $output->writeln("Database: Volunteer saved.");
    }

    private function createTestOrganization(OutputInterface $output): void
    {
        $email = 'organizacion_test@curso.com';
        $password = '123456';
        $cif = 'B11111111';

        $output->writeln("--- Processing Organization: $email ---");

        // A. Firebase
        try {
            try {
                $user = $this->firebaseAuth->getUserByEmail($email);
                $this->firebaseAuth->updateUser($user->uid, [
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => 'Organizacion Test'
                ]);
                $uid = $user->uid;
                $output->writeln("Firebase: User updated.");
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                $user = $this->firebaseAuth->createUser([
                    'email' => $email,
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => 'Organizacion Test'
                ]);
                $uid = $user->uid;
                $output->writeln("Firebase: User created.");
            }
            $this->firebaseAuth->setCustomUserClaims($uid, ['rol' => 'organizacion']);
        } catch (\Throwable $e) {
             $output->writeln("Firebase Error: " . $e->getMessage());
        }

        // B. Database
        $org = $this->em->getRepository(Organizacion::class)->find($cif);
        if (!$org) {
            $org = new Organizacion();
            $org->setCif($cif);
        }

        $org->setNombre("Organizacion Test S.L.");
        $org->setEmail($email);
        $org->setPassword($this->passwordHasher->hashPassword($org, $password));
        $org->setDireccion("Calle Pruebas 123");
        $org->setLocalidad("Pamplona");
        $org->setCp("31000");
        $org->setDescripcion("Empresa de pruebas para QA");
        $org->setContacto("600123456");
        $org->setSector("Tecnología");
        $org->setEstado('aprobado');

        $this->em->persist($org);
        $this->em->flush();
        $output->writeln("Database: Organization saved.");
    }
}
