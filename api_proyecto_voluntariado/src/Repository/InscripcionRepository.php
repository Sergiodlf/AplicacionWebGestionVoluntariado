<?php

namespace App\Repository;

use App\Entity\Inscripcion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscripcion>
 *
 * @method Inscripcion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Inscripcion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Inscripcion[]    findAll()
 * @method Inscripcion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InscripcionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscripcion::class);
    }
}
