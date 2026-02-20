<?php

namespace App\Repository;

use App\Entity\Actividad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actividad>
 */
class ActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actividad::class);
    }
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a');

        // Filter by Approval Status
        if (!empty($filters['estadoAprobacion'])) {
            $qb->andWhere('a.estadoAprobacion = :estadoAprobacion')
               ->setParameter('estadoAprobacion', $filters['estadoAprobacion']);
        }

        // Filter by Execution Status
        if (!empty($filters['estado'])) {
             // Handle "NOT CANCELLED" logic (implicit if not requesting specific state)
            if ($filters['estado'] === 'NOT_CANCELLED') {
                 $qb->andWhere('a.estado != :estadoCancelado')
                   ->setParameter('estadoCancelado', 'CANCELADA');
            } else {
                 $qb->andWhere('a.estado = :estado')
                   ->setParameter('estado', $filters['estado']);
            }
        }

        // Filter by Organization
        if (!empty($filters['organizacion'])) {
            $qb->andWhere('a.organizacion = :organizacion')
               ->setParameter('organizacion', $filters['organizacion']);
        }

        // Exclude specific volunteer (Smart filtering)
        if (!empty($filters['exclude_volunteer_dni'])) {
            $qb->andWhere('a.codActividad NOT IN (
                SELECT IDENTITY(i.actividad) 
                FROM App\Entity\Inscripcion i 
                WHERE i.voluntario = :voluntarioDni
            )')
            ->setParameter('voluntarioDni', $filters['exclude_volunteer_dni']);
        }

        // Filter by Date (History)
        if (isset($filters['history']) && $filters['history'] === false) {
             $qb->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
               ->setParameter('now', new \DateTime());
        }
        
        // Sorting
        $qb->orderBy('a.fechaInicio', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
