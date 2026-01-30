<?php

namespace App\Repository;

use App\Entity\Notificacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notificacion>
 *
 * @method Notificacion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notificacion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notificacion[]    findAll()
 * @method Notificacion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notificacion::class);
    }

    public function findByVoluntario(string $dni)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.voluntario = :dni')
            ->setParameter('dni', $dni)
            ->orderBy('n.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizacion(string $cif)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.organizacion = :cif')
            ->setParameter('cif', $cif)
            ->orderBy('n.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
