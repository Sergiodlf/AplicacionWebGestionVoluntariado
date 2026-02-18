<?php

namespace App\Security\Resolver;

use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;

class VolunteerUserResolver implements UserResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function resolve(string $identifier): ?object
    {
        return $this->entityManager->getRepository(Voluntario::class)->findOneBy(['correo' => $identifier]);
    }

    public function getSupportedRole(): string
    {
        return 'ROLE_VOLUNTARIO';
    }
}
