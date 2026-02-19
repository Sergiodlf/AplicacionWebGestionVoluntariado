<?php

namespace App\Security;

/**
 * Interfaz para entidades que pueden recibir notificaciones.
 * Unifica el acceso a email y FCM token.
 */
interface Notifiable
{
    public function getNotifiableEmail(): string;

    public function getFcmToken(): ?string;
}
