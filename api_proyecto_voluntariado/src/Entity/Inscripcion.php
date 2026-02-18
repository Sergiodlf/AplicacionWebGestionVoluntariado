<?php

namespace App\Entity;

use App\Repository\InscripcionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\InscriptionStatus;

#[ORM\Entity(repositoryClass: InscripcionRepository::class)]
#[ORM\Table(name: 'INSCRIPCIONES')]
class Inscripcion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscripciones')]
    #[ORM\JoinColumn(name: 'DNI_VOLUNTARIO', referencedColumnName: 'DNI', nullable: false, columnDefinition: 'NCHAR(9)')]
    private ?Voluntario $voluntario = null;

    #[ORM\ManyToOne(inversedBy: 'inscripciones')]
    #[ORM\JoinColumn(name: 'CODACTIVIDAD', referencedColumnName: 'CODACTIVIDAD', nullable: false, columnDefinition: 'SMALLINT')]
    private ?Actividad $actividad = null;

    #[ORM\Column(name: 'ESTADO', length: 20, enumType: InscriptionStatus::class)]
    private ?InscriptionStatus $estado = InscriptionStatus::PENDIENTE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoluntario(): ?Voluntario
    {
        return $this->voluntario;
    }

    public function setVoluntario(?Voluntario $voluntario): static
    {
        $this->voluntario = $voluntario;
        return $this;
    }

    public function getActividad(): ?Actividad
    {
        return $this->actividad;
    }

    public function setActividad(?Actividad $actividad): static
    {
        $this->actividad = $actividad;
        return $this;
    }

    public function getEstado(): ?InscriptionStatus
    {
        return $this->estado;
    }

    public function setEstado(InscriptionStatus $estado): static
    {
        $this->estado = $estado;
        return $this;
    }
}
