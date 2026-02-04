<?php

namespace App\Service;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private Messaging $messaging;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private MailerInterface $mailer;

    public function __construct(
        Messaging $messaging, 
        LoggerInterface $logger,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ) {
        $this->messaging = $messaging;
        $this->logger = $logger;
        $this->em = $em;
        $this->mailer = $mailer;
    }

    /**
     * Send email helper
     */
    public function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        try {
            $email = (new Email())
                ->from('notificaciones4v@gmail.com') // Matches authenticated account
                ->to($to)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
            $this->logger->info('NotificationService: Email sent.', ['to' => $to]);
        } catch (\Throwable $e) {
            $this->logger->error('NotificationService: Error sending email.', ['error' => $e->getMessage()]);
            // We don't throw, to avoid breaking the main flow if email fails
        }
    }

    /**
     * Send a notification to a specific user via their FCM token AND persist.
     */
    public function sendToUser(object $user, string $title, string $body, array $data = []): void
    {
        // 1. Persist in Database (For PC/Web History)
        try {
            $notificacion = new \App\Entity\Notificacion();
            $notificacion->setTitulo($title);
            $notificacion->setMensaje($body);
            // $notificacion->setTipo($data['type'] ?? 'GENERAL'); 
            
            if ($user instanceof \App\Entity\Voluntario) {
                $notificacion->setVoluntario($user);
            } elseif ($user instanceof \App\Entity\Organizacion) {
                $notificacion->setOrganizacion($user);
            }

            $this->em->persist($notificacion);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('NotificationService: Error persisting notification to DB.', ['error' => $e->getMessage()]);
        }

        // 2. Send Push Notification (FCM - Existing)
        if (!method_exists($user, 'getFcmToken')) {
             // ... logic continues ...
            $this->logger->warning('NotificationService: User object does not have getFcmToken method.');
            return;
        }

        $token = $user->getFcmToken();

        if (!$token) {
            $this->logger->info('NotificationService: User has no FCM token. Skipping push.');
            $userId = method_exists($user, 'getDni') ? $user->getDni() : (method_exists($user, 'getId') ? $user->getId() : 'UNKNOWN');
            file_put_contents('debug_notification.txt', "WARNING: User $userId has NO FCM TOKEN.\n", FILE_APPEND);
            return;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            $this->logger->info('NotificationService: Push sent to user.', ['token' => $token]);
            file_put_contents('debug_notification.txt', "SUCCESS: Push sent to token: " . substr($token, 0, 10) . "...\n", FILE_APPEND);
        } catch (\Throwable $e) {
            $this->logger->error('NotificationService: Error sending push to user.', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);
            file_put_contents('debug_notification.txt', "ERROR: Firebase Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    /**
     * Send a notification to a TOPIC (e.g. 'admins').
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        // Topics are usually for generic alerts, maybe we don't persist these individually 
        // unless we have a "System Notifications" log. For now, we leave as is.

        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            $this->logger->info('NotificationService: Notification sent to topic.', ['topic' => $topic]);
        } catch (\Throwable $e) {
            $this->logger->error('NotificationService: Error sending notification to topic.', [
                'error' => $e->getMessage(),
                'topic' => $topic
            ]);
        }
    }

    /**
     * Subscribe a token to a topic.
     */
    public function subscribeToTopic(string $token, string $topic): void
    {
        try {
            $this->messaging->subscribeToTopic($topic, $token);
            $this->logger->info('NotificationService: Token subscribed to topic.', ['token' => $token, 'topic' => $topic]);
        } catch (\Throwable $e) {
            $this->logger->error('NotificationService: Error subscribing token to topic.', [
                'error' => $e->getMessage(),
                'token' => $token,
                'topic' => $topic
            ]);
        }
    }
}
