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

    #[ORM\ManyToMany(targetEntity: Voluntario::class, mappedBy: 'intereses')]
    private \Doctrine\Common\Collections\Collection $voluntarios;

    public function __construct()
    {
        $this->voluntarios = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Voluntario>
     */
    public function getVoluntarios(): \Doctrine\Common\Collections\Collection
    {
        return $this->voluntarios;
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
