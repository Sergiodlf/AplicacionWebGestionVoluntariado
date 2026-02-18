<?php

namespace App\Security\Resolver;

use App\Entity\Administrador;
use Doctrine\ORM\EntityManagerInterface;

class AdminUserResolver implements UserResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function resolve(string $identifier): ?object
    {
        return $this->entityManager->getRepository(Administrador::class)->findOneBy(['email' => $identifier]);
    }

    public function getSupportedRole(): string
    {
        return 'ROLE_ADMIN';
    }
}
