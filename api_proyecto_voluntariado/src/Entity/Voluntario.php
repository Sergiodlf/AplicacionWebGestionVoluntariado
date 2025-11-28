<?php

namespace App\Entity;

use App\Repository\VoluntarioRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;


#[ORM\Entity(repositoryClass: VoluntarioRepository::class)]
#[ORM\Table(name: 'VOLUNTARIOS')]
class Voluntario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    private ?string $dni = null;

    #[ORM\Column(length: 50)]
    private ?string $nombre = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $apellido1 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $apellido2 = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $correo = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $zona = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaNacimiento = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $experiencia = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $coche = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cursoCiclos = null;

    // Guardaremos arrays como texto separado por comas (ej: "Inglés, Francés")
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $habilidades = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intereses = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $idiomas = null;

    // ==========================================
    // MÉTODOS DE USER INTERFACE (SEGURIDAD)
    // ==========================================

    public function getUserIdentifier(): string
    {
        return (string) $this->correo;
    }

    public function getRoles(): array
    {
        // Garantizamos que al menos tenga el rol de VOLUNTARIO
        $roles = ['ROLE_VOLUNTARIO'];
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Si guardaras datos temporales sensibles, los borrarías aquí.
    }

    // ==========================================
    // GETTERS Y SETTERS
    // ==========================================

    public function __toString(): string
    {
        return $this->nombre . ' ' . ($this->apellido1 ?? '') . ' ' . ($this->apellido2 ?? '');
    }

    public function getId(): ?string
    {
        return $this->dni;
    }   

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): static
    {
        $this->dni = $dni;
        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellido1(): ?string
    {
        return $this->apellido1;
    }

    public function setApellido1(?string $apellido1): static
    {
        $this->apellido1 = $apellido1;
        return $this;
    }

    public function getApellido2(): ?string
    {
        return $this->apellido2;
    }

    public function setApellido2(?string $apellido2): static
    {
        $this->apellido2 = $apellido2;
        return $this;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): static
    {
        $this->correo = $correo;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getZona(): ?string
    {
        return $this->zona;
    }

    public function setZona(?string $zona): static
    {
        $this->zona = $zona;
        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fechaNacimiento): static
    {
        $this->fechaNacimiento = $fechaNacimiento;
        return $this;
    }

    public function getExperiencia(): ?string
    {
        return $this->experiencia;
    }

    public function setExperiencia(?string $experiencia): static
    {
        $this->experiencia = $experiencia;
        return $this;
    }

    public function isCoche(): ?bool
    {
        return $this->coche;
    }

    public function setCoche(?bool $coche): static
    {
        $this->coche = $coche;
        return $this;
    }

    public function getCursoCiclos(): ?string
    {
        return $this->cursoCiclos;
    }

    public function setCursoCiclos(?string $cursoCiclos): static
    {
        $this->cursoCiclos = $cursoCiclos;
        return $this;
    }

    public function getHabilidades(): ?string
    {
        return $this->habilidades;
    }

    public function setHabilidades(?string $habilidades): static
    {
        $this->habilidades = $habilidades;
        return $this;
    }

    public function getIntereses(): ?string
    {
        return $this->intereses;
    }

    public function setIntereses(?string $intereses): static
    {
        $this->intereses = $intereses;
        return $this;
    }

    public function getIdiomas(): ?string
    {
        return $this->idiomas;
    }

    public function setIdiomas(?string $idiomas): static
    {
        $this->idiomas = $idiomas;
        return $this;
    }
}