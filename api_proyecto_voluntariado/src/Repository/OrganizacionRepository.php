<?php

namespace App\Repository;

use App\Entity\Organizacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organizacion>
 */
class OrganizacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organizacion::class);
    }

    // Aquí podrás añadir funciones personalizadas en el futuro.
    // Por ejemplo: buscarPorSector($sector), buscarPorNombre($nombre), etc.
}