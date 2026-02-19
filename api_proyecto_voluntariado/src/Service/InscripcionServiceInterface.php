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
    public function getAll(?string $estado = null): array;
    public function getById(int $id): ?Inscripcion;
    
    public function countActiveInscriptions(Actividad $actividad): int;
    public function isVolunteerInscribed(Actividad $actividad, Voluntario $voluntario): ?Inscripcion;
    
    public function createInscription(Actividad $actividad, Voluntario $voluntario, ?Inscripcion $existing = null, bool $autoAccept = false): Inscripcion;
    
    public function updateStatus(Inscripcion $inscripcion, string|InscriptionStatus $nuevoEstado): Inscripcion;
    
    public function delete(Inscripcion $inscripcion): void;
    
    public function getByVoluntario(Voluntario $voluntario, ?string $estado = null): array;
    public function getByOrganizacion(string $cif, ?string $estado = null): array;

    /**
     * @param InscriptionStatus|array|string $status
     */
    public function countByStatus($status): int;
}
