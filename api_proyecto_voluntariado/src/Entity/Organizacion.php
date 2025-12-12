<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups; 
use App\Repository\OrganizacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: OrganizacionRepository::class)]
#[ORM\Table(name: 'ORGANIZACIONES')]
class Organizacion implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Grupos de Serialización:
    // 'org:read'  -> Para serializar (enviar a Angular en GET o respuesta POST).
    // 'org:write' -> Para deserializar (recibir de Angular en POST/PUT).

    #[ORM\Id]
    #[ORM\Column(name: 'cif', type: Types::STRING, length: 9, options: ['fixed' => true])]
    #[Groups(['org:read', 'org:write'])]
    private ?string $cif = null;

    #[ORM\Column(name: 'nombre', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $nombre = null;

    // Usamos columnDefinition para evitar que intente cambiar VARCHAR a NVARCHAR
    #[ORM\Column(name: 'email', length: 100, unique: true, columnDefinition: 'VARCHAR(100) NOT NULL UNIQUE')]
    #[Groups(['org:read', 'org:write'])]
    private ?string $email = null;

    #[ORM\Column(name: 'password', length: 255, columnDefinition: 'VARCHAR(255) NOT NULL')]
    // ATENCIÓN: Solo 'org:write'. Se debe recibir, pero NUNCA enviar de vuelta.
    #[Groups(['org:write'])]
    private ?string $password = null;

    #[ORM\Column(name: 'sector', length: 100, nullable: true)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $sector = null;

    // Quitamos nullable: true porque en SQL es NOT NULL
    #[ORM\Column(name: 'direccion', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $direccion = null;

    #[ORM\Column(name: 'localidad', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $localidad = null;

    #[ORM\Column(name: 'descripcion', length: 200)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $descripcion = null;

    #[ORM\OneToMany(mappedBy: 'organizacion', targetEntity: Actividad::class)]
    // Solo 'org:read' para que las actividades relacionadas se incluyan al obtener la organización.
    #[Groups(['org:read'])]
    private Collection $actividades;

    #[ORM\Column(name: 'ESTADO', length: 20, options: ['default' => 'Pendiente'])]
    #[Groups(['org:read', 'org:write'])] // Debe ser legible y posiblemente escribible (por un administrador)
    private ?string $estado = 'Pendiente';

    public function __construct()
    {
        $this->actividades = new ArrayCollection();
    }

    // ==========================================
    // MÉTODOS DE SEGURIDAD
    // ==========================================

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_ORGANIZACION'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        // Limpia datos sensibles después de la autenticación si es necesario
    }

    // ==========================================
    // GETTERS Y SETTERS
    // ==========================================

    public function getCif(): ?string
    {
        return $this->cif;
    }

    public function setCif(string $cif): static
    {
        $this->cif = $cif;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
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

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): static
    {
        $this->direccion = $direccion;
        return $this;
    }
    public function getLocalidad(): ?string
    {
        return $this->localidad;
    }
    public function setLocalidad(?string $localidad): static
    {
        $this->localidad = $localidad;
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

    public function getActividades(): Collection
    {
        return $this->actividades;
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
}
