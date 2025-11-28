<?php

namespace App\Entity;

use App\Repository\ActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActividadRepository::class)]
#[ORM\Table(name: 'ACTIVIDADES')]
class Actividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $codActividad = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 50)]
    private ?string $estado = 'ABIERTA';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaInicio = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipantes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ods = null;

    // --- RELACIONES ---

    // Relación N:1 con Organización (Dueña de la actividad)
    #[ORM\ManyToOne(targetEntity: Organizacion::class)]
    #[ORM\JoinColumn(name: 'cif_organizacion', referencedColumnName: 'cif', nullable: false)]
    private ?Organizacion $organizacion = null;

    // Relación N:M con Voluntarios (Inscripciones)
    // Esto creará la tabla intermedia 'VOLUNTARIOS_ACTIVIDADES' automáticamente
    #[ORM\ManyToMany(targetEntity: Voluntario::class)]
    #[ORM\JoinTable(name: 'VOLUNTARIOS_ACTIVIDADES')]
    #[ORM\JoinColumn(name: 'cod_actividad', referencedColumnName: 'cod_actividad')]
    #[ORM\InverseJoinColumn(name: 'dni_voluntario', referencedColumnName: 'dni')]
    private Collection $voluntariosInscritos;

    // --- CONSTRUCTOR ---
    
    public function __construct()
    {
        // Inicializamos la lista de voluntarios vacía
        $this->voluntariosInscritos = new ArrayCollection();
    }

    // --- GETTERS Y SETTERS ---

    public function getCodActividad(): ?int
    {
        return $this->codActividad;
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

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fechaInicio;
    }

    public function setFechaInicio(?\DateTimeInterface $fechaInicio): static
    {
        $this->fechaInicio = $fechaInicio;
        return $this;
    }

    public function getMaxParticipantes(): ?int
    {
        return $this->maxParticipantes;
    }

    public function setMaxParticipantes(?int $maxParticipantes): static
    {
        $this->maxParticipantes = $maxParticipantes;
        return $this;
    }

    public function getOds(): ?string
    {
        return $this->ods;
    }

    public function setOds(?string $ods): static
    {
        $this->ods = $ods;
        return $this;
    }

    public function getOrganizacion(): ?Organizacion
    {
        return $this->organizacion;
    }

    public function setOrganizacion(?Organizacion $organizacion): static
    {
        $this->organizacion = $organizacion;
        return $this;
    }

    /**
     * @return Collection<int, Voluntario>
     */
    public function getVoluntariosInscritos(): Collection
    {
        return $this->voluntariosInscritos;
    }

    public function addVoluntario(Voluntario $voluntario): static
    {
        if (!$this->voluntariosInscritos->contains($voluntario)) {
            $this->voluntariosInscritos->add($voluntario);
        }

        return $this;
    }

    public function removeVoluntario(Voluntario $voluntario): static
    {
        $this->voluntariosInscritos->removeElement($voluntario);

        return $this;
    }
}