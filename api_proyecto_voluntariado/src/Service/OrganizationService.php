<?php

namespace App\Service;

use App\Entity\Organizacion;
use App\Model\RegistroOrganizacionDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrganizationService
{
    private $entityManager;
    private $passwordHasher;
    private $firebaseAuth;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        \Kreait\Firebase\Contract\Auth $firebaseAuth
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Checks if an organization with the given CIF or Email already exists.
     */
    public function checkDuplicates(string $cif, string $email): ?string
    {
        $repo = $this->entityManager->getRepository(Organizacion::class);
        if ($repo->find($cif)) {
            return 'El CIF ya existe';
        }
        if ($repo->findOneBy(['email' => $email])) {
            return 'El email ya existe';
        }
        return null;
    }

    /**
     * Registers a new organization.
     */
    public function registerOrganization(RegistroOrganizacionDTO $dto, bool $isAdmin = false): Organizacion
    {
        // 1. Create User in Firebase (If not exists)
        try {
            $userProperties = [
                'email' => $dto->email,
                'emailVerified' => false,
                'password' => $dto->password,
                'displayName' => $dto->nombre,
                'disabled' => false,
            ];
            
            try {
                // Attempt creation
                $createdUser = $this->firebaseAuth->createUser($userProperties);
                
                // Set custom claims (rol: organizacion)
                $this->firebaseAuth->setCustomUserClaims($createdUser->uid, ['rol' => 'organizacion']);

            } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
                // Si el email ya existe en Firebase, lanzamos error para avisar al usuario
                throw new \Exception('El correo electrónico ya está registrado en Firebase.');
            }

        } catch (\Exception $e) {
            error_log("Error creating Firebase user (Org): " . $e->getMessage());
            throw $e;
        }

        $org = new Organizacion();
        $org->setCif($dto->cif);
        $org->setNombre($dto->nombre);
        $org->setEmail($dto->email);
        $org->setDireccion($dto->direccion);
        $org->setCp($dto->cp);
        $org->setLocalidad($dto->localidad);
        $org->setDescripcion($dto->descripcion);
        $org->setContacto($dto->contacto);

        // AUTO-ACCEPTANCE LOGIC
        if ($isAdmin) {
             // Coherencia con ActividadController: Mayúsculas
            $org->setEstado('ACEPTADA'); 
        } else {
             $org->setEstado('PENDIENTE'); 
        }

        if ($dto->sector) $org->setSector($dto->sector);

        $hashedPassword = $this->passwordHasher->hashPassword($org, $dto->password);
        $org->setPassword($hashedPassword);

        $this->entityManager->getConnection()->executeStatement("SET DATEFORMAT ymd");
        $this->entityManager->persist($org);
        $this->entityManager->flush();

        return $org;
    }
}
