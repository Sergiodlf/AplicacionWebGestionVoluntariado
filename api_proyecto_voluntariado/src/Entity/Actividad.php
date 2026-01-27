<?php

namespace App\Entity;

use App\Repository\ActividadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// IMPORTANTE: Importamos Ignore para romper el bucle infinito
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\Groups;



#[ORM\Entity(repositoryClass: ActividadRepository::class)]
#[ORM\Table(name: 'ACTIVIDADES')]
class Actividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'CODACTIVIDAD', type: Types::SMALLINT)]
    #[Groups(['org:read'])]
    private ?int $codActividad = null;

    #[ORM\Column(name: 'NOMBRE', length: 40)]
    #[Groups(['org:read'])]
    private ?string $nombre = null;

    #[ORM\Column(name: 'ESTADO', length: 255)]
    #[Groups(['org:read'])]
    private ?string $estado = 'En Curso';

    #[ORM\Column(name: 'ESTADO_APROBACION', length: 20, options: ['default' => 'PENDIENTE'])]
    #[Groups(['org:read'])]
    private ?string $estadoAprobacion = 'PENDIENTE';

    #[ORM\Column(name: 'ESTADO_NEW', length: 255, nullable: true)]
    #[Groups(['org:read'])]
    private ?string $estadoNew = null;

    #[ORM\Column(name: 'DIRECCION', type: Types::STRING, length: 40)]
    #[Groups(['org:read'])]
    private ?string $direccion = null;

    #[ORM\Column(name: 'SECTOR', type: Types::STRING, length: 50, nullable: true)]
    #[Groups(['org:read'])]
    private ?string $sector = null;
    
    #[ORM\Column(name: 'DESCRIPCION', type: Types::TEXT, nullable: true)]
    #[Groups(['org:read'])]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'FECHA_INICIO', type: Types::DATETIME_MUTABLE)]
    #[Groups(['org:read'])]
    private ?\DateTimeInterface $fechaInicio = null;
    
    #[ORM\Column(name: 'FECHA_FIN', type: Types::DATETIME_MUTABLE)]
    #[Groups(['org:read'])]
    private ?\DateTimeInterface $fechaFin = null;

    // ... (existing code)

    /*
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }
    */

    public function getHorario(): string
    {
        if ($this->fechaInicio && $this->fechaFin) {
            return $this->fechaInicio->format('d/m/Y H:i') . ' - ' . $this->fechaFin->format('H:i');
        }
        return 'Sin horario definido';
    }

    #[ORM\Column(name: 'MAX_PARTICIPANTES', type: Types::SMALLINT)]
    #[Groups(['org:read'])]
    private ?int $maxParticipantes = null;

    #[ORM\ManyToMany(targetEntity: ODS::class)]
    #[ORM\JoinTable(name: 'ACTIVIDADES_ODS')]
    #[ORM\JoinColumn(name: 'CODACTIVIDAD', referencedColumnName: 'CODACTIVIDAD')]
    #[ORM\InverseJoinColumn(name: 'ODS_ID', referencedColumnName: 'id')]
    #[Groups(['org:read'])]
    private Collection $ods;

    #[ORM\ManyToMany(targetEntity: Habilidad::class)]
    #[ORM\JoinTable(name: 'ACTIVIDADES_HABILIDADES')]
    #[ORM\JoinColumn(name: 'CODACTIVIDAD', referencedColumnName: 'CODACTIVIDAD')]
    #[ORM\InverseJoinColumn(name: 'HABILIDAD_ID', referencedColumnName: 'id')]
    #[Groups(['org:read'])]
    private Collection $habilidades;

    #[ORM\ManyToMany(targetEntity: Necesidad::class)]
    #[ORM\JoinTable(name: 'ACTIVIDADES_NECESIDADES')]
    #[ORM\JoinColumn(name: 'CODACTIVIDAD', referencedColumnName: 'CODACTIVIDAD')]
    #[ORM\InverseJoinColumn(name: 'NECESIDAD_ID', referencedColumnName: 'id')]
    #[Groups(['org:read'])]
    private Collection $necesidades;


    // --- RELACIONES ---

    #[ORM\ManyToOne(targetEntity: Organizacion::class, inversedBy: 'actividades')]
    #[ORM\JoinColumn(name: 'CIF_EMPRESA', referencedColumnName: 'CIF', nullable: true, columnDefinition: 'NCHAR(9)')]
    #[Ignore] // <--- AÑADIDO: Evita que al pedir una actividad, intente serializar toda la organización y sus infinitas actividades
    private ?Organizacion $organizacion = null;

    // CAMBIO REALIZADO: RELACIÓN ONE-TO-MANY (Sustituye a ManyToMany)
    #[ORM\OneToMany(mappedBy: 'actividad', targetEntity: Inscripcion::class, orphanRemoval: true)]
    #[Ignore] 
    private Collection $inscripciones;

    public function __construct()
    {
        $this->inscripciones = new ArrayCollection();
        $this->ods = new ArrayCollection();
        $this->habilidades = new ArrayCollection();
        $this->necesidades = new ArrayCollection();
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

    public function getEstadoAprobacion(): ?string
    {
        return $this->estadoAprobacion;
    }

    public function setEstadoAprobacion(string $estadoAprobacion): static
    {
        $this->estadoAprobacion = $estadoAprobacion;
        return $this;
    }

    public function getEstadoNew(): ?string
    {
        return $this->estadoNew;
    }

    public function setEstadoNew(?string $estadoNew): static
    {
        $this->estadoNew = $estadoNew;
        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): static
    {
        $this->direccion = $direccion;
        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;
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

    /**
     * @return Collection<int, ODS>
     */
    public function getOds(): Collection
    {
        return $this->ods;
    }

    public function addOd(ODS $od): static
    {
        if (!$this->ods->contains($od)) {
            $this->ods->add($od);
        }
        return $this;
    }

    public function removeOd(ODS $od): static
    {
        $this->ods->removeElement($od);
        return $this;
    }

    /**
     * @return Collection<int, Habilidad>
     */
    public function getHabilidades(): Collection
    {
        return $this->habilidades;
    }

    public function addHabilidad(Habilidad $habilidad): static
    {
        if (!$this->habilidades->contains($habilidad)) {
            $this->habilidades->add($habilidad);
        }
        return $this;
    }

    public function removeHabilidad(Habilidad $habilidad): static
    {
        $this->habilidades->removeElement($habilidad);
        return $this;
    }

    /**
     * @return Collection<int, Necesidad>
     */
    public function getNecesidades(): Collection
    {
        return $this->necesidades;
    }

    public function addNecesidad(Necesidad $necesidad): static
    {
        if (!$this->necesidades->contains($necesidad)) {
            $this->necesidades->add($necesidad);
        }
        return $this;
    }

    public function removeNecesidad(Necesidad $necesidad): static
    {
        $this->necesidades->removeElement($necesidad);
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
            $inscripcion->setActividad($this);
        }

        return $this;
    }

    public function removeInscripcion(Inscripcion $inscripcion): static
    {
        if ($this->inscripciones->removeElement($inscripcion)) {
            // set the owning side to null (unless already changed)
            if ($inscripcion->getActividad() === $this) {
                $inscripcion->setActividad(null);
            }
        }

        return $this;
    } 
}