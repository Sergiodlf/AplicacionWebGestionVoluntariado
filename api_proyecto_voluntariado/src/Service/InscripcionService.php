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
        return $this->entityManager->getRepository(Inscripcion::class)->countActiveByActivity($actividad);
    }

    /**
     * Checks if a volunteer is already inscribed in an activity.
     */
    public function isVolunteerInscribed(Actividad $actividad, Voluntario $voluntario): bool
    {
        return $this->entityManager->getRepository(Inscripcion::class)->findByVolunteerAndActivity($voluntario, $actividad) !== null;
    }

    /**
     * Creates a new inscription.
     */
    public function createInscription(Actividad $actividad, Voluntario $voluntario): Inscripcion
    {
        $inscripcion = new Inscripcion();
        $inscripcion->setActividad($actividad);
        $inscripcion->setVoluntario($voluntario);
        $inscripcion->setEstado('PENDIENTE'); 

        $this->entityManager->persist($inscripcion);
        $this->entityManager->flush();

        return $inscripcion;
    }
}
