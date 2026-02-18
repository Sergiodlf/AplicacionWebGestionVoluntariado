<?php

namespace App\Service;

use App\Entity\Voluntario;
use App\Model\RegistroVoluntarioDTO;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\VolunteerStatus;


class VolunteerService implements VolunteerServiceInterface
{
    private $entityManager;

    private $habilidadRepository;
    private $interesRepository;
    private EmailServiceInterface $emailService;

    public function __construct(
        EntityManagerInterface $entityManager, 
        \App\Repository\HabilidadRepository $habilidadRepository,
        \App\Repository\InteresRepository $interesRepository,
        \App\Repository\CicloRepository $cicloRepository,
        \Kreait\Firebase\Contract\Auth $firebaseAuth,
        EmailServiceInterface $emailService,
        NotificationManagerInterface $notificationManager
    ) {
        $this->entityManager = $entityManager;
        $this->habilidadRepository = $habilidadRepository;
        $this->interesRepository = $interesRepository;
        $this->cicloRepository = $cicloRepository;
        $this->firebaseAuth = $firebaseAuth;
        $this->emailService = $emailService;
        $this->notificationManager = $notificationManager;
    }

    // --- VALIDATION METHODS ---

    public function checkDuplicates(string $dni, string $email): ?string
    {
        $repo = $this->entityManager->getRepository(Voluntario::class);
        
        if ($repo->find($dni)) {
            return 'El DNI ya está registrado';
        }
        
        if ($repo->findOneBy(['correo' => $email])) {
            return 'El email ya está registrado';
        }
        
        return null;
    }

    public function validateDTO(RegistroVoluntarioDTO $dto): ?string
    {
        // 1. Validar DNI
        if (!$this->isValidDni($dto->dni)) {
            return 'El DNI/NIE no tiene un formato válido';
        }

        // 2. Validar Edad (Mínimo 16 años)
        if ($dto->fechaNacimiento) {
            try {
                $fechaNac = new \DateTime($dto->fechaNacimiento);
                $hoy = new \DateTime();
                $edad = $hoy->diff($fechaNac)->y;
                
                if ($edad < 16) {
                    return 'Debes tener al menos 16 años para registrarte';
                }
            } catch (\Exception $e) {
                return 'Formato de fecha de nacimiento inválido';
            }
        }

        return null;
    }

    private function isValidDni(string $dni): bool
    {
        $dni = strtoupper(trim($dni));
        
        // Regex para DNI (8 números + letra) o NIE (X/Y/Z + 7 números + letra)
        if (!preg_match('/^([0-9]{8}|[XYZ][0-9]{7})[TRWAGMYFPDXBNJZSQVHLCKE]$/', $dni)) {
            return false;
        }

        $standardDni = $dni;
        
        // Si es NIE, reemplazar letra inicial por número
        if (str_starts_with($dni, 'X')) $standardDni = '0' . substr($dni, 1);
        elseif (str_starts_with($dni, 'Y')) $standardDni = '1' . substr($dni, 1);
        elseif (str_starts_with($dni, 'Z')) $standardDni = '2' . substr($dni, 1);

        $numbers = (int)substr($standardDni, 0, -1);
        $letter = substr($standardDni, -1);
        
        $validLetters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $calculatedIndex = $numbers % 23;
        $expectedLetter = $validLetters[$calculatedIndex];

        return $letter === $expectedLetter;
    }

    /**
     * Registers a new volunteer.
     */
    public function registerVolunteer(RegistroVoluntarioDTO $dto, bool $isAdmin = false): Voluntario
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
                $createdUser = $this->firebaseAuth->createUser($userProperties);
                $this->firebaseAuth->setCustomUserClaims($createdUser->uid, ['rol' => 'voluntario']);

                try {
                    $link = $this->firebaseAuth->getEmailVerificationLink($dto->email);
                    $this->emailService->sendEmail(
                        $dto->email,
                        'Verifica tu correo - Gestión Voluntariado',
                        sprintf('<p>Hola %s,</p><p>Para activar tu cuenta, por favor verifica tu correo haciendo clic en el siguiente enlace:</p><p><a href="%s">Verificar Correo</a></p>', $dto->nombre, $link)
                    );
                } catch (\Throwable $e) {
                    // El email de verificación no es crítico
                }

            } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
                $existingUser = $this->firebaseAuth->getUserByEmail($dto->email);
                $this->firebaseAuth->setCustomUserClaims($existingUser->uid, ['rol' => 'voluntario']);
            }

        } catch (\Exception $e) {
            throw $e;
        }

        $voluntario = new Voluntario();
        $voluntario->setDni($dto->dni);
        $voluntario->setCorreo($dto->email);
        
        // Mapping name and surnames
        $partes = explode(' ', trim($dto->nombre));
        $voluntario->setNombre($partes[0] ?? '');
        $voluntario->setApellido1($partes[1] ?? 'Sin Apellido');
        $voluntario->setApellido2($partes[2] ?? '');



        // Optional fields
        if ($dto->zona) $voluntario->setZona($dto->zona);
        if ($dto->fechaNacimiento) {
            $voluntario->setFechaNacimiento(new \DateTime($dto->fechaNacimiento));
        } else {
            $voluntario->setFechaNacimiento(new \DateTime('2000-01-01'));
        }
        
        $voluntario->setExperiencia($dto->experiencia ?? 'Sin experiencia previa');
        $voluntario->setCoche($dto->coche ?? false);

        // Handle Ciclo
        if ($dto->ciclo) {
            $cicloData = $dto->ciclo;
            $cicloEntity = null;

            if (is_array($cicloData) && isset($cicloData['nombre']) && isset($cicloData['curso'])) {
                $cicloEntity = $this->cicloRepository->findOneBy([
                    'nombre' => $cicloData['nombre'], 
                    'curso' => $cicloData['curso']
                ]);
            } elseif (is_string($cicloData)) {
                 $cicloEntity = $this->cicloRepository->findOneBy(['nombre' => $cicloData]);
            }

            if ($cicloEntity) {
                $voluntario->setCiclo($cicloEntity);
            } else {
                // Ciclo no encontrado, se ignora
            }
        }
        
        // Link Habilidades
        if (!empty($dto->habilidades)) {
            foreach ($dto->habilidades as $id) {
                $h = $this->habilidadRepository->find($id);
                if ($h) {
                    $voluntario->addHabilidad($h);
                }
            }
        }

        // Link Intereses
        if (!empty($dto->intereses)) {
            foreach ($dto->intereses as $id) {
                $i = $this->interesRepository->find($id);
                if ($i) {
                    $voluntario->addInterese($i);
                }
            }
        }
        $voluntario->setIdiomas($dto->idiomas ?? []);
        $voluntario->setDisponibilidad($dto->disponibilidad ?? []);
        
        // AUTO-ACCEPTANCE LOGIC
        $estadoInicial = $isAdmin ? VolunteerStatus::ACEPTADO : VolunteerStatus::PENDIENTE;
        $voluntario->setEstadoVoluntario($estadoInicial);

        $this->entityManager->getConnection()->executeStatement("SET DATEFORMAT ymd");
        $this->entityManager->persist($voluntario);
        $this->entityManager->flush();

        return $voluntario;
    }
    public function getById(string $dni): ?Voluntario
    {
        return $this->entityManager->getRepository(Voluntario::class)->find($dni);
    }

    public function updateProfile(Voluntario $voluntario, array $data): Voluntario
    {
        if (isset($data['nombre'])) $voluntario->setNombre($data['nombre']);
        if (isset($data['apellido1'])) $voluntario->setApellido1($data['apellido1']);
        if (isset($data['apellido2'])) $voluntario->setApellido2($data['apellido2']);
        if (isset($data['zona'])) $voluntario->setZona($data['zona']);
        if (isset($data['experiencia'])) $voluntario->setExperiencia($data['experiencia']);
        if (isset($data['coche'])) $voluntario->setCoche($data['coche']);
        if (isset($data['disponibilidad']) && is_array($data['disponibilidad'])) $voluntario->setDisponibilidad(array_values($data['disponibilidad']));
        if (isset($data['idiomas']) && is_array($data['idiomas'])) $voluntario->setIdiomas(array_values($data['idiomas']));

        // Relaciones ManyToMany (Habilidades)
        if (isset($data['habilidades']) && is_array($data['habilidades'])) {
            $voluntario->getHabilidades()->clear();
            foreach ($data['habilidades'] as $item) {
                $habilidad = null;
                if (is_array($item) && isset($item['id'])) {
                    $habilidad = $this->habilidadRepository->find($item['id']);
                } elseif (is_numeric($item)) {
                    $habilidad = $this->habilidadRepository->find($item);
                } elseif (is_string($item)) {
                    $habilidad = $this->habilidadRepository->findOneBy(['nombre' => $item]);
                }

                if ($habilidad) {
                    $voluntario->addHabilidad($habilidad);
                }
            }
        }

        // Relaciones ManyToMany (Intereses)
        if (isset($data['intereses']) && is_array($data['intereses'])) {
            $voluntario->getIntereses()->clear();
            foreach ($data['intereses'] as $item) {
                $interes = null;
                if (is_array($item) && isset($item['id'])) {
                    $interes = $this->interesRepository->find($item['id']);
                } elseif (is_numeric($item)) {
                    $interes = $this->interesRepository->find($item);
                } elseif (is_string($item)) {
                    $interes = $this->interesRepository->findOneBy(['nombre' => $item]);
                }

                if ($interes) {
                    $voluntario->addInterese($interes);
                }
            }
        }
        
        // Ciclo
        if (isset($data['ciclo'])) {
            $cicloData = $data['ciclo'];
            $cicloObj = null;

            if (is_array($cicloData) && isset($cicloData['nombre']) && isset($cicloData['curso'])) {
                $cicloObj = $this->cicloRepository->findOneBy([
                    'nombre' => $cicloData['nombre'], 
                    'curso' => $cicloData['curso']
                ]);
            } elseif (is_string($cicloData)) {
                $parts = [];
                if (preg_match('/^(.*)\s\((\d+)º\)$/', $cicloData, $parts)) {
                    $nombre = trim($parts[1]);
                    $curso = (int)$parts[2];
                    $cicloObj = $this->cicloRepository->findOneBy(['nombre' => $nombre, 'curso' => $curso]);
                } else {
                    $cicloObj = $this->cicloRepository->findOneBy(['nombre' => $cicloData]);
                }
            }

            if ($cicloObj) {
                $voluntario->setCiclo($cicloObj);
            }
        }

        // FCM Token
        if (isset($data['fcmToken'])) {
            $voluntario->setFcmToken($data['fcmToken']);
        }

        $this->entityManager->flush();
        return $voluntario;
    }
    public function getAll(array $criteria = []): array
    {
        return $this->entityManager->getRepository(Voluntario::class)->findBy($criteria);
    }

    public function getByEmail(string $email): ?Voluntario
    {
        return $this->entityManager->getRepository(Voluntario::class)->findOneBy(['correo' => $email]);
    }

    public function countByStatus(string|VolunteerStatus $status): int
    {
        if (is_string($status)) {
            $status = VolunteerStatus::tryFrom(strtoupper($status)) ?? VolunteerStatus::PENDIENTE;
        }
        return $this->entityManager->getRepository(Voluntario::class)->count(['estadoVoluntario' => $status]);
    }

    public function updateStatus(Voluntario $voluntario, string|VolunteerStatus $status): void
    {
        if (is_string($status)) {
            $status = VolunteerStatus::tryFrom(strtoupper($status)) ?? VolunteerStatus::PENDIENTE;
        }
        $oldStatus = $voluntario->getEstadoVoluntario();
        $voluntario->setEstadoVoluntario($status);
        $this->entityManager->flush();

        if ($oldStatus !== VolunteerStatus::ACEPTADO && $status === VolunteerStatus::ACEPTADO) {
            $this->notificationManager->notifyUser(
                $voluntario,
                "¡Cuenta Aceptada!",
                "Tu cuenta de voluntario ha sido aceptada por un administrador. ¡Ya puedes inscribirte en actividades!"
            );
        }
    }
}
