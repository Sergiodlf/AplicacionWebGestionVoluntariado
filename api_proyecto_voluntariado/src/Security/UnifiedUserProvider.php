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
        $domainUser = null;
        $roles = [];
        $type = null;

        // 0. Try to find an Administrador (Highest priority)
        $domainUser = $this->entityManager->getRepository(Administrador::class)->findOneBy(['email' => $identifier]);
        if ($domainUser) {
            $roles = ['ROLE_ADMIN'];
            $type = 'admin';
        }

        // 1. If not found, try to find a Voluntario
        if (!$domainUser) {
            $domainUser = $this->entityManager->getRepository(Voluntario::class)->findOneBy(['correo' => $identifier]);
            if ($domainUser) {
                $roles = ['ROLE_VOLUNTARIO'];
                $type = 'voluntario';
            }
        }

        // 2. If not found, try to find an Organizacion
        if (!$domainUser) {
            $domainUser = $this->entityManager->getRepository(Organizacion::class)->findOneBy(['email' => $identifier]);
            if ($domainUser) {
                $roles = ['ROLE_ORGANIZACION'];
                $type = 'organizacion';
            }
        }

        if (!$domainUser) {
             // Allow temporary admin access via whitelist if not in DB (Legacy/Dev)
            if ($identifier === 'admin@admin.com' || $identifier === 'adminTest5666@gmail.com') {
                 return new \App\Security\User\SecurityUser($identifier, ['ROLE_ADMIN'], null);
            }
            throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
        }

        return new \App\Security\User\SecurityUser($identifier, $roles, $domainUser);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === \App\Security\User\SecurityUser::class;
    }
}
