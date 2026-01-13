<?php

namespace App\Entity;

use App\Repository\HabilidadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: HabilidadRepository::class)]
#[ORM\Table(name: 'HABILIDADES')]
class Habilidad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actividad:read', 'voluntario:read', 'habilidad:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['actividad:read', 'voluntario:read', 'habilidad:read'])]
    private ?string $nombre = null;

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
}
