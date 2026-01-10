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

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
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
        $voluntario->setHabilidades($dto->habilidades ?? []);
        $voluntario->setIntereses($dto->intereses ?? []);
        $voluntario->setIdiomas($dto->idiomas ?? []);
        $voluntario->setDisponibilidad($dto->disponibilidad ?? []);
        $voluntario->setEstadoVoluntario('PENDIENTE');

        $this->entityManager->persist($voluntario);
        $this->entityManager->flush();

        return $voluntario;
    }
}
