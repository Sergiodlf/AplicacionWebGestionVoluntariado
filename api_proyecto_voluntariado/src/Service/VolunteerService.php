<?php

namespace App\Service;

use App\Entity\Voluntario;
use App\Model\RegistroVoluntarioDTO;
use Doctrine\ORM\EntityManagerInterface;


class VolunteerService
{
    private $entityManager;

    private $habilidadRepository;
    private $interesRepository;
    private $notificationService; // NEW

    public function __construct(
        EntityManagerInterface $entityManager, 
        \App\Repository\HabilidadRepository $habilidadRepository,
        \App\Repository\InteresRepository $interesRepository,
        \App\Repository\CicloRepository $cicloRepository,
        \Kreait\Firebase\Contract\Auth $firebaseAuth,
        NotificationService $notificationService // INJECTED
    ) {
        $this->entityManager = $entityManager;
        $this->habilidadRepository = $habilidadRepository;
        $this->interesRepository = $interesRepository;
        $this->cicloRepository = $cicloRepository;
        $this->firebaseAuth = $firebaseAuth;
        $this->notificationService = $notificationService;
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
        error_log('Registering volunteer: ' . $dto->email);
        
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
                error_log("Firebase user created: " . $createdUser->uid);
                
                // Set custom claims (rol: voluntario)
                $this->firebaseAuth->setCustomUserClaims($createdUser->uid, ['rol' => 'voluntario']);

                // --- NEW: SEND VERIFICATION EMAIL ---
                try {
                    $link = $this->firebaseAuth->getEmailVerificationLink($dto->email);
                    $this->notificationService->sendEmail(
                        $dto->email,
                        'Verifica tu correo - Gestión Voluntariado',
                        sprintf('<p>Hola %s,</p><p>Para activar tu cuenta, por favor verifica tu correo haciendo clic en el siguiente enlace:</p><p><a href="%s">Verificar Correo</a></p>', $dto->nombre, $link)
                    );
                } catch (\Throwable $e) {
                    error_log("Error sending verification email: " . $e->getMessage());
                }
                // ------------------------------------

            } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
                // Si el email ya existe en Firebase, recuperamos el usuario para asignarle claims
                $existingUser = $this->firebaseAuth->getUserByEmail($dto->email);
                $this->firebaseAuth->setCustomUserClaims($existingUser->uid, ['rol' => 'voluntario']);
            }

        } catch (\Exception $e) {
            error_log("Error creating Firebase user: " . $e->getMessage());
            // Depending on requirements, we might want to stop here.
            // But if it's a "weak password" error from Firebase, we should probably return it.
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
                error_log("Ciclo not found: " . print_r($cicloData, true));
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
        $estadoInicial = $isAdmin ? 'ACEPTADO' : 'PENDIENTE';
        $voluntario->setEstadoVoluntario($estadoInicial);

        $this->entityManager->getConnection()->executeStatement("SET DATEFORMAT ymd");
        $this->entityManager->persist($voluntario);
        $this->entityManager->flush();

        return $voluntario;
    }
}
