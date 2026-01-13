<?php

namespace App\Entity;

use App\Repository\InteresRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: InteresRepository::class)]
#[ORM\Table(name: 'INTERESES')]
class Interes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['voluntario:read', 'interes:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['voluntario:read', 'interes:read'])]
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
