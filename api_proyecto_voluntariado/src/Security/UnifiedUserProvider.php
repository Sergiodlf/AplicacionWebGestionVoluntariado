<?php

namespace App\Security;

use App\Entity\Administrador;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UnifiedUserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // 0. Try to find an Administrador (Highest priority)
        $user = $this->entityManager->getRepository(Administrador::class)->findOneBy(['email' => $identifier]);

        // 1. If not found, try to find a Voluntario
        if (!$user) {
            $user = $this->entityManager->getRepository(Voluntario::class)->findOneBy(['correo' => $identifier]);
        }

        // 2. If not found, try to find an Organizacion
        if (!$user) {
            $user = $this->entityManager->getRepository(Organizacion::class)->findOneBy(['email' => $identifier]);
        }

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found in Administradores, Voluntarios or Organizaciones.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Reload user from database to ensure fresh data
        // We can just use the identifier (email) to reload
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === Administrador::class || $class === Voluntario::class || $class === Organizacion::class;
    }
}
