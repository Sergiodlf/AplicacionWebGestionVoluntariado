<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Implementación del servicio de notificaciones push delegando en FirebaseService.
 */
class PushNotificationService implements PushNotificationServiceInterface
{
    public function __construct(
        private FirebaseServiceInterface $firebaseService,
        private LoggerInterface $logger
    ) {}

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $this->firebaseService->sendPush($token, $title, $body, $data);
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $this->firebaseService->sendToTopic($topic, $title, $body, $data);
    }

    public function subscribeToTopic(string $token, string $topic): void
    {
        $this->firebaseService->subscribeToTopic($token, $topic);
    }
}
