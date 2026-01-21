<?php

namespace App\Entity;

use App\Repository\NecesidadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NecesidadRepository::class)]
#[ORM\Table(name: 'NECESIDADES')]
class Necesidad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actividad:read', 'necesidad:read', 'org:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['actividad:read', 'necesidad:read', 'org:read'])]
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
