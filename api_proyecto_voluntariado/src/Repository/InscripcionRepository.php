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

    /**
     * @return Inscripcion[] Returns an array of Inscripcion objects
     */
    public function findByOrganizacionAndEstado(string $cif, ?string $estado = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->join('i.actividad', 'a')
            ->join('a.organizacion', 'o')
            ->where('o.cif = :cif')
            ->setParameter('cif', $cif);

        if ($estado) {
            $qb->andWhere('i.estado = :estado')
               ->setParameter('estado', $estado);
        }

        return $qb->getQuery()->getResult();
    }

    public function countActiveByActivity(\App\Entity\Actividad $actividad): int
    {
        return $this->createQueryBuilder('i')
            ->select('count(i.id)')
            ->where('i.actividad = :actividad')
            ->andWhere('i.estado IN (:estados)')
            ->setParameter('actividad', $actividad)
            ->setParameter('estados', ['PENDIENTE', 'CONFIRMADO', 'CONFIRMADA', 'ACEPTADA', 'EN_CURSO', 'EN CURSO']) // Maintain compatibility with legacy strings for now
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByVolunteerAndActivity(\App\Entity\Voluntario $voluntario, \App\Entity\Actividad $actividad): ?Inscripcion
    {
        return $this->findOneBy(['voluntario' => $voluntario, 'actividad' => $actividad]);
    }
}
