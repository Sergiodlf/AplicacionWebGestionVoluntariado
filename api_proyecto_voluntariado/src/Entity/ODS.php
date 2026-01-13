<?php

namespace App\Entity;

use App\Repository\ODSRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ODSRepository::class)]
#[ORM\Table(name: 'ODS')]
class ODS
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actividad:read', 'ods:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['actividad:read', 'ods:read'])]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['actividad:read', 'ods:read'])]
    private ?string $descripcion = null;

    #[ORM\Column(length: 7, nullable: true)] // Hex color #RRGGBB
    #[Groups(['actividad:read', 'ods:read'])]
    private ?string $color = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }
}
