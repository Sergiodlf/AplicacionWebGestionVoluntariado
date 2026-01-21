<?php

namespace App\Service;

use App\Entity\Voluntario;
use App\Model\RegistroVoluntarioDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class VolunteerService
{
    private $entityManager;
    private $passwordHasher;
    private $habilidadRepository;
    private $interesRepository;
    private $cicloRepository;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        \App\Repository\HabilidadRepository $habilidadRepository,
        \App\Repository\InteresRepository $interesRepository,
        \App\Repository\CicloRepository $cicloRepository
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->habilidadRepository = $habilidadRepository;
        $this->interesRepository = $interesRepository;
        $this->cicloRepository = $cicloRepository;
    }

    /**
     * Checks if a volunteer with the given DNI or Email already exists.
     */
    public function checkDuplicates(string $dni, string $email): ?string
    {
        $repo = $this->entityManager->getRepository(Voluntario::class);
        if ($repo->findOneBy(['dni' => $dni])) {
            return 'El DNI ya existe';
        }
        if ($repo->findOneBy(['correo' => $email])) {
            return 'El correo ya existe';
        }
        return null;
    }

    /**
     * Registers a new volunteer.
     */
    public function registerVolunteer(RegistroVoluntarioDTO $dto): Voluntario
    {
        error_log('Registering volunteer: ' . $dto->email);
        error_log('Disponibilidad received: ' . json_encode($dto->disponibilidad));

        $voluntario = new Voluntario();
        $voluntario->setDni($dto->dni);
        $voluntario->setCorreo($dto->email);
        
        // Mapping name and surnames
        $partes = explode(' ', trim($dto->nombre));
        $voluntario->setNombre($partes[0] ?? '');
        $voluntario->setApellido1($partes[1] ?? 'Sin Apellido');
        $voluntario->setApellido2($partes[2] ?? '');

        // Password hashing
        $hashed = $this->passwordHasher->hashPassword($voluntario, $dto->password);
        $voluntario->setPassword($hashed);

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
        $voluntario->setEstadoVoluntario('PENDIENTE');

        $this->entityManager->getConnection()->executeStatement("SET DATEFORMAT ymd");
        $this->entityManager->persist($voluntario);
        $this->entityManager->flush();

        return $voluntario;
    }
}
