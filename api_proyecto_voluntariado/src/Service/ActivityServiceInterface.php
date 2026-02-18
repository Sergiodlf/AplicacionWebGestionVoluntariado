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
    public function getAll(array $criteria = []): array;
    public function updateActivity(int $id, array $data): ?Actividad;
    public function updateActivityStatus(int $id, string $nuevoEstado, string $tipo): ?Actividad;
    public function deleteActivity(int $id): bool;
    public function countVisible(): int;
    public function countPending(): int;
}
