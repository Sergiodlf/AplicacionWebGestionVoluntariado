<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class AdminUser implements UserInterface
{
    private string $email;
    private array $roles;

    public function __construct(string $email, array $roles = ['ROLE_ADMIN'])
    {
        $this->email = $email;
        $this->roles = $roles;
    }

    public function getRoles(): array
    {
        // Garantizar al menos ROLE_ADMIN
        $roles = $this->roles;
        $roles[] = 'ROLE_ADMIN';
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // No hay credenciales sensibles en memoria
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
