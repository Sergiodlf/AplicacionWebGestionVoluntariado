<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Voluntario;
use App\Enum\InscriptionStatus;

/**
 * Interfaz para el servicio de inscripciones.
 */
interface InscripcionServiceInterface
{
    public function createInscription(Actividad $actividad, Voluntario $voluntario, ?Inscripcion $existing = null): Inscripcion;
    public function updateStatus(int $id, InscriptionStatus $status): ?Inscripcion;
    public function deleteInscription(int $id): bool;
    public function isVolunteerInscribed(Actividad $actividad, Voluntario $voluntario): ?Inscripcion;
    public function countByActivity(Actividad $actividad): int;
    public function getByVolunteer(string $dni): array;
    public function getByOrganization(string $cif): array;

    /**
     * @param InscriptionStatus|InscriptionStatus[] $statuses
     */
    public function countByStatus(InscriptionStatus|array $statuses): int;
}
