<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;

class InscripcionService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Counts active inscriptions for an activity.
     */
    public function countActiveInscriptions(Actividad $actividad): int
    {
        $conn = $this->entityManager->getConnection();
        $sql = "
            SELECT COUNT(*) as total 
            FROM INSCRIPCIONES 
            WHERE CODACTIVIDAD = :actividadId
            AND ESTADO IN ('PENDIENTE', 'CONFIRMADO', 'CONFIRMADA', 'ACEPTADA', 'EN_CURSO', 'EN CURSO')
        ";
        
        $stmt = $conn->executeQuery($sql, ['actividadId' => $actividad->getCodActividad()]);
        $result = $stmt->fetchAssociative();
        
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Checks if a volunteer is already inscribed in an activity.
     */
    public function isVolunteerInscribed(Actividad $actividad, Voluntario $voluntario): bool
    {
        $repo = $this->entityManager->getRepository(Inscripcion::class);
        $existing = $repo->findOneBy([
            'actividad' => $actividad,
            'voluntario' => $voluntario
        ]);
        
        return $existing !== null;
    }

    /**
     * Creates a new inscription.
     */
    public function createInscription(Actividad $actividad, Voluntario $voluntario): Inscripcion
    {
        $inscripcion = new Inscripcion();
        $inscripcion->setActividad($actividad);
        $inscripcion->setVoluntario($voluntario);
        // $inscripcion->setFechaInscripcion(new \DateTime());
        $inscripcion->setEstado('PENDIENTE');

        $this->entityManager->persist($inscripcion);
        $this->entityManager->flush();

        return $inscripcion;
    }
}
