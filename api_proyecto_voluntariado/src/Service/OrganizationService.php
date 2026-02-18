<?php

namespace App\Service;

use App\Entity\Organizacion;
use App\Model\RegistroOrganizacionDTO;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\OrganizationStatus;


class OrganizationService implements OrganizationServiceInterface
{
    private $entityManager;

    private EmailServiceInterface $emailService;
    private FirebaseServiceInterface $firebaseService;
    private NotificationManagerInterface $notificationManager;

    public function __construct(
        EntityManagerInterface $entityManager, 
        FirebaseServiceInterface $firebaseService,
        EmailServiceInterface $emailService,
        NotificationManagerInterface $notificationManager
    ) {
        $this->entityManager = $entityManager;
        $this->firebaseService = $firebaseService;
        $this->emailService = $emailService;
        $this->notificationManager = $notificationManager;
    }



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
     * Registra una nueva organización.
     */
    public function registerOrganization(RegistroOrganizacionDTO $dto, bool $isAdmin = false): Organizacion
    {

        // 1. Create User in Firebase (Centralized via FirebaseService)
        try {
            $uid = $this->firebaseService->createUser($dto->email, $dto->password, $dto->nombre);
            $this->firebaseService->setUserRole($uid, 'organizacion');

            try {
                $link = $this->firebaseService->getEmailVerificationLink($dto->email);
                $this->emailService->sendEmail(
                    $dto->email,
                    'Verifica tu correo - Gestión Voluntariado',
                    sprintf('<p>Hola %s,</p><p>Gracias por registrar tu organización. Por favor verifica tu correo en el siguiente enlace:</p><p><a href="%s">Verificar Correo</a></p>', $dto->nombre, $link)
                );
            } catch (\Throwable $e) {
                // El email de verificación no es crítico
            }

        } catch (\Throwable $e) {
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
            $org->setEstado(OrganizationStatus::APROBADO); 
        } else {
             $org->setEstado(OrganizationStatus::PENDIENTE); 
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

    public function updateState(string $cif, string|OrganizationStatus $newState): ?Organizacion
    {
        $org = $this->getByCif($cif);
        if (!$org) {
            return null;
        }

        if (is_string($newState)) {
            $newState = OrganizationStatus::tryFrom(strtolower($newState)) ?? OrganizationStatus::PENDIENTE;
        }

        $oldEstado = $org->getEstado();
        $org->setEstado($newState);
        $this->entityManager->flush();

        if ($oldEstado !== OrganizationStatus::APROBADO && $newState === OrganizationStatus::APROBADO) {
            $this->notificationManager->notifyUser(
                $org,
                "¡Organización Aceptada!",
                "Tu organización ha sido aceptada en la plataforma. Ya puedes publicar actividades y gestionar voluntarios."
            );
        }

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

        // FCM Token
        if (isset($data['fcmToken'])) {
            $org->setFcmToken($data['fcmToken']);
        }

        $this->entityManager->flush();
        return $org;
    }

    public function updateProfile(Organizacion $org, array $data): Organizacion
    {

        return $this->updateOrganization($org->getCif(), $data);
    }
    public function countByStatus(string|OrganizationStatus $status): int
    {
        if (is_string($status)) {
            $status = OrganizationStatus::tryFrom(strtolower($status)) ?? OrganizationStatus::PENDIENTE;
        }
        return $this->entityManager->getRepository(Organizacion::class)->count(['estado' => $status]);
    }
}
