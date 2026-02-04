<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups; // 👈 Importación esencial
use App\Repository\OrganizacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizacionRepository::class)]
#[ORM\Table(name: 'ORGANIZACIONES')]
class Organizacion implements UserInterface
{
    // Grupos de Serialización:
    // 'org:read'  -> Para serializar (enviar a Angular en GET o respuesta POST).
    // 'org:write' -> Para deserializar (recibir de Angular en POST/PUT).

    #[ORM\Id]
    #[ORM\Column(name: 'CIF', type: Types::STRING, length: 9, options: ['fixed' => true])] 
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $cif = null; 

    #[ORM\Column(name: 'NOMBRE', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $nombre = null;

    // Usamos columnDefinition para evitar que intente cambiar VARCHAR a NVARCHAR
    #[ORM\Column(name: 'EMAIL', length: 100, unique: true, columnDefinition: 'VARCHAR(100) NOT NULL UNIQUE')]
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;



    #[ORM\Column(name: 'SECTOR', length: 100, nullable: true)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $sector = null;

    // Quitamos nullable: true porque en SQL es NOT NULL
    #[ORM\Column(name: 'DIRECCION', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $direccion = null;

    #[ORM\Column(name: 'LOCALIDAD', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $localidad = null;

    // MARTILLAZO AQUÍ: Definimos CHAR(5) explícitamente para que no choque con la Constraint
    #[ORM\Column(name: 'CP', type: Types::STRING, length: 5, columnDefinition: 'CHAR(5) NOT NULL')] 
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $cp = null;

    #[ORM\Column(name: 'DESCRIPCION', length: 200)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $descripcion = null;
    
    #[ORM\Column(name: 'CONTACTO', length: 40)]
    #[Groups(['org:read', 'org:write'])]
    #[Assert\NotBlank]
    private ?string $contacto = null;

    #[ORM\Column(name: 'ESTADO', length: 20)]
    #[Groups(['org:read'])]
    private ?string $estado = 'pendiente';

    #[ORM\Column(name: 'FCM_TOKEN', length: 255, nullable: true)]
    #[Groups(['org:read', 'org:write'])]
    private ?string $fcmToken = null;

    #[ORM\OneToMany(mappedBy: 'organizacion', targetEntity: Actividad::class)]
    // Solo 'org:read' para que las actividades relacionadas se incluyan al obtener la organización.
    #[Groups(['org:read'])] 
    private Collection $actividades;
    

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
    
    public function getCp(): ?string { return $this->cp; }
    public function setCp(string $cp): static { $this->cp = $cp; return $this; }
    
    public function getContacto(): ?string { return $this->contacto; }
    public function setContacto(string $contacto): static { $this->contacto = $contacto; return $this; }

    public function getEstado(): ?string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function getFcmToken(): ?string { return $this->fcmToken; }
    public function setFcmToken(?string $token): static { $this->fcmToken = $token; return $this; }

    public function getActividades(): Collection { return $this->actividades; }
}