<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
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

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPassword(): ?string
    {
        return null; // External Auth (Firebase)
    }
}
