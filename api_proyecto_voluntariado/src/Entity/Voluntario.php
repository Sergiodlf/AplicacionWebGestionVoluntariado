<?php

namespace App\Entity;

use App\Repository\VoluntarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: VoluntarioRepository::class)]
#[ORM\Table(name: 'VOLUNTARIOS')]
class Voluntario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'DNI', type: Types::STRING, length: 9, options: ['fixed' => true])]
    private ?string $dni = null;

    #[ORM\Column(name: 'NOMBRE', length: 40)]
    private ?string $nombre = null;

    #[ORM\Column(name: 'APELLIDO1', length: 40)]
    private ?string $apellido1 = null;

    #[ORM\Column(name: 'APELLIDO2', length: 40)]
    private ?string $apellido2 = null;

    #[ORM\Column(name: 'CORREO', length: 40, unique: true)]
    private ?string $correo = null;

    #[ORM\Column(name: 'PASSWORD', length: 255, columnDefinition: 'VARCHAR(255) NOT NULL')]
    private ?string $password = null;

    #[ORM\Column(name: 'ZONA', length: 100, nullable: true)]
    private ?string $zona = null;

    #[ORM\Column(name: 'FECHA_NACIMIENTO', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fechaNacimiento = null;

    #[ORM\Column(name: 'EXPERIENCIA', length: 200, nullable: true)]
    private ?string $experiencia = null;

    #[ORM\Column(name: 'COCHE', type: Types::BOOLEAN)]
    private ?bool $coche = null;

    #[ORM\ManyToOne(targetEntity: Ciclo::class)]
    #[ORM\JoinColumn(name: 'CURSO_CICLOS', referencedColumnName: 'CURSO', nullable: true)]
    #[ORM\JoinColumn(name: 'NOMBRE_CICLOS', referencedColumnName: 'NOMBRE', nullable: true)]
    private ?Ciclo $ciclo = null;

    #[ORM\ManyToMany(targetEntity: Habilidad::class)]
    #[ORM\JoinTable(name: 'VOLUNTARIOS_HABILIDADES')]
    #[ORM\JoinColumn(name: 'DNI', referencedColumnName: 'DNI')]
    #[ORM\InverseJoinColumn(name: 'HABILIDAD_ID', referencedColumnName: 'id')]
    private Collection $habilidades;

    #[ORM\ManyToMany(targetEntity: Interes::class)]
    #[ORM\JoinTable(name: 'VOLUNTARIOS_INTERESES')]
    #[ORM\JoinColumn(name: 'DNI', referencedColumnName: 'DNI')]
    #[ORM\InverseJoinColumn(name: 'INTERES_ID', referencedColumnName: 'id')]
    private Collection $intereses;

    #[ORM\Column(name: 'DISPONIBILIDAD', type: Types::TEXT, nullable: true)]
    private ?string $disponibilidad = null;
    
    #[ORM\Column(name: 'IDIOMAS', type: Types::TEXT, nullable: true)]
    private ?string $idiomas = null;

    #[ORM\Column(name: 'ESTADO_VOLUNTARIO', length: 20)]
    private ?string $estadoVoluntario = 'PENDIENTE';

    #[ORM\OneToMany(mappedBy: 'voluntario', targetEntity: Inscripcion::class, orphanRemoval: true)]
    private Collection $inscripciones;


    public function __construct()
    {
        $this->inscripciones = new ArrayCollection();
        $this->habilidades = new ArrayCollection();
        $this->intereses = new ArrayCollection();
    }


    // ... Resto de métodos de seguridad (getUserIdentifier, getRoles...) ...
    public function getUserIdentifier(): string { return (string) $this->correo; }
    public function getRoles(): array { return ['ROLE_VOLUNTARIO']; }
    public function eraseCredentials(): void {}

    // ... Getters y Setters básicos ...
    public function getDni(): ?string { return $this->dni; }
    public function setDni(string $dni): static { $this->dni = $dni; return $this; }
    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }
    // ... (añade el resto de getters básicos aquí) ...
    public function getApellido1(): ?string { return $this->apellido1; }
    public function setApellido1(?string $a): static { $this->apellido1 = $a; return $this; }
    public function getApellido2(): ?string { return $this->apellido2; }
    public function setApellido2(?string $a): static { $this->apellido2 = $a; return $this; }
    public function getCorreo(): ?string { return $this->correo; }
    public function setCorreo(string $c): static { $this->correo = $c; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $p): static { $this->password = $p; return $this; }
    public function getZona(): ?string { return $this->zona; }
    public function setZona(?string $z): static { $this->zona = $z; return $this; }
    public function getFechaNacimiento(): ?\DateTimeInterface { return $this->fechaNacimiento; }
    public function setFechaNacimiento(?\DateTimeInterface $f): static { $this->fechaNacimiento = $f; return $this; }
    public function getExperiencia(): ?string { return $this->experiencia; }
    public function setExperiencia(?string $e): static { $this->experiencia = $e; return $this; }
    public function isCoche(): ?bool { return $this->coche; }
    public function setCoche(?bool $c): static { $this->coche = $c; return $this; }
    public function getCiclo(): ?Ciclo { return $this->ciclo; }
    public function setCiclo(?Ciclo $c): static { $this->ciclo = $c; return $this; }
    /**
     * @return Collection<int, Habilidad>
     */
    public function getHabilidades(): Collection
    {
        return $this->habilidades;
    }

    public function addHabilidad(Habilidad $h): static
    {
        if (!$this->habilidades->contains($h)) {
            $this->habilidades->add($h);
        }
        return $this;
    }

    public function removeHabilidad(Habilidad $h): static
    {
        $this->habilidades->removeElement($h);
        return $this;
    }

    /**
     * @return Collection<int, Interes>
     */
    public function getIntereses(): Collection
    {
        return $this->intereses;
    }

    public function addInterese(Interes $i): static
    {
        if (!$this->intereses->contains($i)) {
            $this->intereses->add($i);
        }
        return $this;
    }

    public function removeInterese(Interes $i): static
    {
        $this->intereses->removeElement($i);
        return $this;
    }


    public function getDisponibilidad(): array
    {
        if ($this->disponibilidad === null) {
            return [];
        }
        $decoded = json_decode($this->disponibilidad, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        $raw = trim($this->disponibilidad, '"\'');
        return empty($raw) ? [] : [$raw];
    }

    public function setDisponibilidad(?array $d): static
    {
        $this->disponibilidad = $d ? json_encode($d) : null;
        return $this;
    }

    public function getIdiomas(): array
    {
        if ($this->idiomas === null) {
            return [];
        }
        $decoded = json_decode($this->idiomas, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        $raw = trim($this->idiomas, '"\'');
        return empty($raw) ? [] : [$raw];
    }

    public function setIdiomas(?array $i): static
    {
        $this->idiomas = $i ? json_encode($i) : null;
        return $this;
    }

    public function getEstadoVoluntario(): ?string { return $this->estadoVoluntario; }
    public function setEstadoVoluntario(string $estado): static { $this->estadoVoluntario = $estado; return $this; }


    // MÉTODOS DE LA RELACIÓN
    /**
     * @return Collection<int, Inscripcion>
     */
    public function getInscripciones(): Collection
    {
        return $this->inscripciones;
    }

    public function addInscripcion(Inscripcion $inscripcion): static
    {
        if (!$this->inscripciones->contains($inscripcion)) {
            $this->inscripciones->add($inscripcion);
            $inscripcion->setVoluntario($this);
        }

        return $this;
    }

    public function removeInscripcion(Inscripcion $inscripcion): static
    {
        if ($this->inscripciones->removeElement($inscripcion)) {
            // set the owning side to null (unless already changed)
            if ($inscripcion->getVoluntario() === $this) {
                $inscripcion->setVoluntario(null);
            }
        }

        return $this;
    }
}