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

    #[ORM\Column(name: 'ESTADO', length: 40)]
    #[Groups(['org:read'])]
    private ?string $estado = 'En Curso';

    #[ORM\Column(name: 'DIRECCION', type: Types::STRING, length: 40)]
    #[Groups(['org:read'])]
    private ?string $direccion = null;

    // #[ORM\Column(type: Types::TEXT, nullable: true)]
    // private ?string $descripcion = null;

    #[ORM\Column(name: 'FECHA_INICIO', type: Types::DATETIME_MUTABLE)]
    #[Groups(['org:read'])]
    private ?\DateTimeInterface $fechaInicio = null;
    
    #[ORM\Column(name: 'FECHA_FIN', type: Types::DATETIME_MUTABLE)]
    #[Groups(['org:read'])]
    private ?\DateTimeInterface $fechaFin = null;

    #[ORM\Column(name: 'MAX_PARTICIPANTES', type: Types::SMALLINT)]
    #[Groups(['org:read'])]
    private ?int $maxParticipantes = null;

    #[ORM\Column(name: 'ODS', type: Types::TEXT, nullable: true)]
    #[Groups(['org:read'])]
    private ?string $ods = null;

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

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): static
    {
        $this->direccion = $direccion;
        return $this;
    }

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

    public function getOds(): array
    {
        if ($this->ods === null) {
            return [];
        }

        // 1. Intentar JSON
        $decoded = json_decode($this->ods, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // 2. Fallback: CSV (Legacy "ODS1, ODS2")
        if (str_contains($this->ods, ',')) {
            return array_map('trim', explode(',', $this->ods));
        }

        // 3. Fallback: String simple
        $raw = trim($this->ods, '"\'');
        return empty($raw) ? [] : [$raw];
    }

    public function setOds(?array $ods): static
    {
        if (empty($ods)) {
            $this->ods = null;
        } else {
            $this->ods = json_encode($ods);
        }
        return $this;
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['org:read'])]
    private ?string $habilidades = null;

    public function getHabilidades(): array
    {
        if ($this->habilidades === null) {
            return [];
        }

        // Intentar decodificar JSON
        $decoded = json_decode($this->habilidades, true);

        // Si es JSON válido y es un array, devolverlo
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // FALLBACK: Si falla (por ejemplo, el usuario editó la BD a mano y puso "Cocina"),
        // devolvemos el valor crudo como un único elemento del array.
        // Limpiamos comillas extra si las hubiera.
        $raw = trim($this->habilidades, '"\'');
        
        return empty($raw) ? [] : [$raw];
    }

    public function setHabilidades(?array $habilidades): static
    {
        if (empty($habilidades)) {
            $this->habilidades = null;
        } else {
            // Guardamos siempre como JSON válido
            $this->habilidades = json_encode($habilidades);
        }
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