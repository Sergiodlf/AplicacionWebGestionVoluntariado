<?php

namespace App\Service;

/**
 * Interfaz para el servicio de notificaciones push (FCM).
 */
interface PushNotificationServiceInterface
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): void;
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void;
    public function subscribeToTopic(string $token, string $topic): void;
}
