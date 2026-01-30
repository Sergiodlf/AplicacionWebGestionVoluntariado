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
    private $notificationService; // NEW

    public function __construct(
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        \Kreait\Firebase\Contract\Auth $firebaseAuth,
        NotificationService $notificationService // INJECTED
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->firebaseAuth = $firebaseAuth;
        $this->notificationService = $notificationService;
    }

    // ... checkDuplicates and validateDTO remain unchanged ...

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

    public function validateDTO(RegistroOrganizacionDTO $dto): ?string
    {
        if (!$this->isValidCif($dto->cif)) {
            return 'CIF inválido';
        }
        return null;
    }

    private function isValidCif(string $cif): bool
    {
        // ... (Keep existing implementation logic) ...
         $cif = strtoupper(trim($cif));
        if (!preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $cif)) {
            return false;
        }

        $letters = 'JABCDEFGHI';
        $digits = substr($cif, 1, 7);
        $letter = $cif[0];
        $control = $cif[8];

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$digits[$i];
            if ($i % 2 === 0) {
                $temp = $digit * 2;
                $sum += $temp > 9 ? $temp - 9 : $temp;
            } else {
                $sum += $digit;
            }
        }

        $lastDigitSum = $sum % 10;
        $controlDigit = $lastDigitSum === 0 ? 0 : 10 - $lastDigitSum;

        if (is_numeric($control)) {
            return (int)$control === $controlDigit;
        }

        $calculatedLetter = $letters[$controlDigit];
        return $control === $calculatedLetter;
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

                // --- NEW: SEND VERIFICATION EMAIL ---
                try {
                    $link = $this->firebaseAuth->getEmailVerificationLink($dto->email);
                    $this->notificationService->sendEmail(
                        $dto->email,
                        'Verifica tu correo - Gestión Voluntariado',
                        sprintf('<p>Hola %s,</p><p>Gracias por registrar tu organización. Por favor verifica tu correo en el siguiente enlace:</p><p><a href="%s">Verificar Correo</a></p>', $dto->nombre, $link)
                    );
                } catch (\Throwable $e) {
                    error_log("Error sending verification email (Org): " . $e->getMessage());
                }
                // ------------------------------------

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
