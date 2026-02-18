<?php

namespace App\Service;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

/**
 * Implementación concreta del servicio de notificaciones push usando Firebase Cloud Messaging.
 */
class PushNotificationService implements PushNotificationServiceInterface
{
    public function __construct(
        private Messaging $messaging,
        private LoggerInterface $logger
    ) {}

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) {
            $this->logger->info('PushNotificationService: No token provided. Skipping push.');
            return;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            $this->logger->info('PushNotificationService: Push sent.', ['token' => $token]);
        } catch (\Throwable $e) {
            $this->logger->error('PushNotificationService: Error sending push.', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);
        }
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            $this->logger->info('PushNotificationService: Sent to topic.', ['topic' => $topic]);
        } catch (\Throwable $e) {
            $this->logger->error('PushNotificationService: Error sending to topic.', [
                'error' => $e->getMessage(),
                'topic' => $topic
            ]);
        }
    }

    public function subscribeToTopic(string $token, string $topic): void
    {
        try {
            $this->messaging->subscribeToTopic($topic, $token);
            $this->logger->info('PushNotificationService: Subscribed to topic.', ['token' => $token, 'topic' => $topic]);
        } catch (\Throwable $e) {
            $this->logger->error('PushNotificationService: Error subscribing to topic.', [
                'error' => $e->getMessage(),
                'token' => $token,
                'topic' => $topic
            ]);
        }
    }
}
