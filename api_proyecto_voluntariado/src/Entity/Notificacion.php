<?php

namespace App\Entity;

use App\Repository\NotificacionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificacionRepository::class)]
class Notificacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mensaje = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column]
    private ?bool $leido = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'voluntario_dni', referencedColumnName: 'DNI', nullable: true)]
    private ?Voluntario $voluntario = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'organizacion_cif', referencedColumnName: 'CIF', nullable: true)]
    private ?Organizacion $organizacion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tipo = null; // e.g. 'INSCRIPCION', 'SISTEMA'

    public function __construct()
    {
        $this->fecha = new \DateTime();
        $this->leido = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getMensaje(): ?string
    {
        return $this->mensaje;
    }

    public function setMensaje(string $mensaje): static
    {
        $this->mensaje = $mensaje;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function isLeido(): ?bool
    {
        return $this->leido;
    }

    public function setLeido(bool $leido): static
    {
        $this->leido = $leido;

        return $this;
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

    public function getOrganizacion(): ?Organizacion
    {
        return $this->organizacion;
    }

    public function setOrganizacion(?Organizacion $organizacion): static
    {
        $this->organizacion = $organizacion;

        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }
}
