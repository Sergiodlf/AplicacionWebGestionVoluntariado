<?php

namespace App\Security\User;

use App\Entity\Administrador;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Clase User de aplicación única.
 * Normaliza la información de los diferentes tipos de usuario (Voluntario, Organización, Administrador).
 * Permite que el resto de la app use un objeto estándar.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?object $domainUser = null;

    public function __construct(
        private string $identifier,
        private array $roles = [],
        ?object $domainUser = null
    ) {
        $this->domainUser = $domainUser;
    }

    public function getDomainUser(): ?object
    {
        return $this->domainUser;
    }

    /**
     * Devuelve el ID único del usuario (DNI, CIF o ID).
     */
    public function getId(): ?string
    {
        if ($this->domainUser instanceof Voluntario) {
            return $this->domainUser->getDni();
        }
        if ($this->domainUser instanceof Organizacion) {
            return $this->domainUser->getCif();
        }
        if ($this->domainUser instanceof Administrador) {
            return (string) $this->domainUser->getId();
        }
        return null;
    }

    public function getEmail(): string
    {
        // En Voluntario es getCorreo(), en Organización y Admin es getEmail()
        if ($this->domainUser instanceof Voluntario) {
            return (string) $this->domainUser->getCorreo();
        }
        if ($this->domainUser instanceof Organizacion) {
            return (string) $this->domainUser->getEmail();
        }
        if ($this->domainUser instanceof Administrador) {
            return (string) $this->domainUser->getEmail();
        }
        
        return $this->identifier;
    }

    public function getName(): ?string
    {
        if ($this->domainUser instanceof Voluntario) {
            return $this->domainUser->getNombre() . ' ' . $this->domainUser->getApellido1();
        }
        if ($this->domainUser instanceof Organizacion) {
            return $this->domainUser->getNombre();
        }
        if ($this->domainUser instanceof Administrador) {
            return $this->domainUser->getNombre();
        }
        return 'Usuario';
    }

    public function getType(): string
    {
        if ($this->domainUser instanceof Voluntario) {
            return 'voluntario';
        }
        if ($this->domainUser instanceof Organizacion) {
            return 'organizacion';
        }
        if ($this->domainUser instanceof Administrador) {
            return 'admin';
        }
        return 'desconocido';
    }

    public function getSerializationGroups(): array
    {
        $groups = ['user:read'];

        if ($this->domainUser instanceof Voluntario) {
            $groups[] = 'voluntario:read';
        } elseif ($this->domainUser instanceof Organizacion) {
            $groups[] = 'org:read';
        }

        return $groups;
    }

    // --- Métodos de UserInterface ---

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantizar que todos los usuarios tengan al menos ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Si almacenas datos sensibles temporales, límpialos aquí
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPassword(): ?string
    {
        return null; // Autenticación externa (Firebase)
    }
}
