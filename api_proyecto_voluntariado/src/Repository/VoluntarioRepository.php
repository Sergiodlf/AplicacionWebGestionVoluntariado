<?php

namespace App\Repository;

use App\Entity\Voluntario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voluntario>
 */
class VoluntarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voluntario::class);
    }

    // Aquí podrás añadir funciones para buscar voluntarios por zona, etc.
}