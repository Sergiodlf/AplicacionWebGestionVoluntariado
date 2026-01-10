<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CICLOS')]
class Ciclo
{
    #[ORM\Id]
    #[ORM\Column(name: 'CURSO', type: Types::SMALLINT)]
    private ?int $curso = null;

    #[ORM\Id]
    #[ORM\Column(name: 'NOMBRE', type: Types::STRING, length: 100)]
    private ?string $nombre = null;

    public function getCurso(): ?int
    {
        return $this->curso;
    }

    public function setCurso(int $curso): static
    {
        $this->curso = $curso;

        return $this;
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
    
    public function __toString(): string {
        return $this->nombre . ' (' . $this->curso . 'º)';
    }
}