<?php

namespace App\Service;

use App\Entity\Notificable; // Oops, I named it Notifiable in the file but let's check
use App\Entity\Notifiable;
use App\Entity\Notificacion;

/**
 * Interfaz para la gestión de persistencia de notificaciones y coordinación.
 */
interface NotificationManagerInterface
{
    public function notifyUser(Notifiable $user, string $title, string $body, array $data = []): void;
    public function getNotificationsForUser(Notifiable $user): array;
    public function getNotificationById(int $id): ?Notificacion;
    public function markAsRead(Notificacion $notificacion): void;
}
