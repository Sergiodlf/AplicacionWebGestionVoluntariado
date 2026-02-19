<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Organizacion;

/**
 * Interfaz para el servicio de actividades.
 */
interface ActivityServiceInterface
{
    public function createActivity(array $data, Organizacion $organizacion): ?Actividad;
    public function getActivityById(int $id): ?Actividad;
    public function activityExists(string $nombre, Organizacion $org): bool;
    
    // Updated to match ActivityService
    public function getActivitiesByFilters(array $filters): array;
    
    // Updated to match ActivityService
    public function updateActivity(Actividad $actividad, array $data): Actividad;
    
    // Updated to match ActivityService
    public function updateActivityStatus(int $id, string $nuevoEstado, ?string $tipo = null): array;
    
    // Updated to match ActivityService
    public function deleteActivity(int $id): string;
    
    public function countVisible(): int;
    public function countPending(): int;
}
