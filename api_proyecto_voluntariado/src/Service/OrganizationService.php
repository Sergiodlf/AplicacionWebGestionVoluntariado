<?php

namespace App\Service;

use App\Entity\Organizacion;
use App\Model\RegistroOrganizacionDTO;
use Doctrine\ORM\EntityManagerInterface;


class OrganizationService
{
    private $entityManager;

    private $notificationService; // NEW

    public function __construct(
        EntityManagerInterface $entityManager, 
        \Kreait\Firebase\Contract\Auth $firebaseAuth,
        NotificationService $notificationService // INJECTED
    ) {
        $this->entityManager = $entityManager;
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
    /**
     * Registers a new organization.
     */
    public function registerOrganization(RegistroOrganizacionDTO $dto, bool $isAdmin = false): Organizacion
    {
        // ... (existing implementation) ...
        // For brevity in this replacement, I'm assuming I should overwrite the file or be careful with the replacement.
        // Actually, since I have to replace a chunk, let me just add the new methods at the end of the class.
        // But wait, I need to make sure I don't lose the registerOrganization method.
        // The instruction says "Add missing service methods", so I'll try to append them or replace the end of the class.
        // Better: I will use a larger context to ensure I don't break registerOrganization.
        
        // ... Re-implementing registerOrganization to be safe ...
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
                $createdUser = $this->firebaseAuth->createUser($userProperties);
                $this->firebaseAuth->setCustomUserClaims($createdUser->uid, ['rol' => 'organizacion']);

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

            } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
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
        $org->setSector($dto->sector);

        if ($isAdmin) {
            $org->setEstado('aprobado'); 
        } else {
             $org->setEstado('pendiente'); 
        }

        $this->entityManager->persist($org);
        $this->entityManager->flush();

        return $org;
    }

    public function getAll(array $criteria = []): array
    {
        return $this->entityManager->getRepository(Organizacion::class)->findBy($criteria);
    }

    public function getByCif(string $cif): ?Organizacion
    {
        return $this->entityManager->getRepository(Organizacion::class)->find($cif);
    }

    public function getByEmail(string $email): ?Organizacion
    {
        return $this->entityManager->getRepository(Organizacion::class)->findOneBy(['email' => $email]);
    }

    public function deleteOrganization(string $cif): bool
    {
        $org = $this->getByCif($cif);
        if (!$org) {
            return false;
        }

        $this->entityManager->remove($org);
        $this->entityManager->flush();
        return true;
    }

    public function updateState(string $cif, string $newState): ?Organizacion
    {
        $org = $this->getByCif($cif);
        if (!$org) {
            return null;
        }

        $org->setEstado($newState);
        $this->entityManager->flush();
        return $org;
    }

    public function updateOrganization(string $cif, array $data): ?Organizacion
    {
        $org = $this->getByCif($cif);
        if (!$org) {
            return null;
        }

        if (isset($data['nombre'])) $org->setNombre($data['nombre']);
        if (isset($data['email'])) $org->setEmail($data['email']);
        if (isset($data['sector'])) $org->setSector($data['sector']);
        if (isset($data['direccion'])) $org->setDireccion($data['direccion']);
        if (isset($data['localidad'])) $org->setLocalidad($data['localidad']);
        if (isset($data['cp'])) $org->setCp($data['cp']);
        if (isset($data['descripcion'])) $org->setDescripcion($data['descripcion']);
        if (isset($data['contacto'])) $org->setContacto($data['contacto']);

        // FCM Token (Added support here or separate method? Let's add it here to unify)
        if (isset($data['fcmToken'])) {
            $org->setFcmToken($data['fcmToken']);
        }

        $this->entityManager->flush();
        return $org;
    }

    public function updateProfile(Organizacion $org, array $data): Organizacion
    {
        // Wrapper around updateOrganization but accepts Entity directly
        // Reuse logic
        return $this->updateOrganization($org->getCif(), $data);
    }
    public function countByStatus(string $status): int
    {
        return $this->entityManager->getRepository(Organizacion::class)->count(['estado' => $status]);
    }
}
