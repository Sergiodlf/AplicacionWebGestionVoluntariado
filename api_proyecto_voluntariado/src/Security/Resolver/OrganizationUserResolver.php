<?php

namespace App\Security\Resolver;

use App\Entity\Organizacion;
use Doctrine\ORM\EntityManagerInterface;

class OrganizationUserResolver implements UserResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function resolve(string $identifier): ?object
    {
        return $this->entityManager->getRepository(Organizacion::class)->findOneBy(['email' => $identifier]);
    }

    public function getSupportedRole(): string
    {
        return 'ROLE_ORGANIZACION';
    }
}
