<?php

namespace App\Entity;

use App\Repository\ActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// IMPORTANTE: Importamos Ignore para romper el bucle infinito
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: ActividadRepository::class)]
#[ORM\Table(name: 'ACTIVIDADES')]
class Actividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'cod_actividad', type: Types::SMALLINT)]
    private ?int $codActividad = null;

    #[ORM\Column(name: 'nombre', length: 40)]
    private ?string $nombre = null;

    #[ORM\Column(name: 'estado', length: 40)]
    private ?string $estado = 'En Curso';

    #[ORM\Column( name: 'descripcion', type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'fecha_inicio', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fechaInicio = null;
    
    #[ORM\Column(name: 'fecha_fin', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fechaFin = null;

    #[ORM\Column(name: 'max_participantes', type: Types::SMALLINT)]
    private ?int $maxParticipantes = null;

    #[ORM\Column(name: 'ods', type: Types::TEXT, nullable: true)]
    private ?string $ods = null;

    // --- RELACIONES ---

    #[ORM\ManyToOne(targetEntity: Organizacion::class, inversedBy: 'actividades')]
    #[ORM\JoinColumn(name: 'cif_organizacion', referencedColumnName: 'cif', nullable: true, columnDefinition: 'NCHAR(9)')]
    #[Ignore] // <--- AÑADIDO: Evita que al pedir una actividad, intente serializar toda la organización y sus infinitas actividades
    private ?Organizacion $organizacion = null;

    // CAMBIO REALIZADO:
    // Hemos eliminado la configuración de JoinTable de aquí y la hemos movido a Voluntario.php
    // Ahora Actividad es el lado "pasivo" (mappedBy), lo que evita el conflicto de orden en la PK.
    #[ORM\ManyToMany(targetEntity: Voluntario::class, mappedBy: 'actividades')]
    #[Ignore] // <--- AÑADIDO: Evita que al pedir una actividad, descargue todos los voluntarios y sus datos completos
    private Collection $voluntariosInscritos;

    public function __construct()
    {
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

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fechaFin;
    }

    public function setFechaFin(?\DateTimeInterface $fechaFin): static
    {
        $this->fechaFin = $fechaFin;
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
            $voluntario->addActividad($this);
        }

        return $this;
    }

    public function removeVoluntario(Voluntario $voluntario): static
    {
        if ($this->voluntariosInscritos->removeElement($voluntario)) {
            $voluntario->removeActividad($this);
        }

        return $this;
    } 
}