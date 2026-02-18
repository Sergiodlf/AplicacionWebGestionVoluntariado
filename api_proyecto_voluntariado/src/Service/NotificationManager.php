<?php

namespace App\Service;

use App\Entity\Notifiable;
use App\Entity\Notificacion;
use App\Entity\Voluntario;
use App\Entity\Organizacion;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementación de la gestión de persistencia de notificaciones.
 * Coordina con PushNotificationService para el envío en tiempo real.
 */
class NotificationManager implements NotificationManagerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private PushNotificationServiceInterface $pushService
    ) {}

    public function notifyUser(Notifiable $user, string $title, string $body, array $data = []): void
    {
        // 1. Persistir en base de datos
        try {
            $notificacion = new Notificacion();
            $notificacion->setTitulo($title);
            $notificacion->setMensaje($body);
            
            if ($user instanceof Voluntario) {
                $notificacion->setVoluntario($user);
            } elseif ($user instanceof Organizacion) {
                $notificacion->setOrganizacion($user);
            }

            $this->em->persist($notificacion);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('NotificationManager: Error persisting notification.', ['error' => $e->getMessage()]);
        }

        // 2. Enviar Push
        $this->pushService->sendToToken($user->getFcmToken(), $title, $body, $data);
    }

    public function getNotificationsForUser(Notifiable $user): array
    {
        $repo = $this->em->getRepository(Notificacion::class);
        
        if ($user instanceof Voluntario) {
            return $repo->findByVoluntario($user->getDni());
        } elseif ($user instanceof Organizacion) {
            return $repo->findByOrganizacion($user->getCif());
        }
        
        return [];
    }

    public function getNotificationById(int $id): ?Notificacion
    {
        return $this->em->getRepository(Notificacion::class)->find($id);
    }

    public function markAsRead(Notificacion $notificacion): void
    {
        $notificacion->setLeido(true);
        $this->em->flush();
    }
}
