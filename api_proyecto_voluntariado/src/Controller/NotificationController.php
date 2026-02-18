<?php

namespace App\Controller;

use App\Service\NotificationService;
use App\Entity\Voluntario;
use App\Entity\Organizacion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notificaciones', name: 'api_notificaciones_')]
class NotificationController extends AbstractController
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Ensure we have the domain user entity
        if (method_exists($user, 'getDomainUser')) {
            $user = $user->getDomainUser();
        }

        $notificaciones = $this->notificationService->getNotificationsForUser($user);

        $data = [];
        foreach ($notificaciones as $n) {
            $data[] = [
                'id' => $n->getId(),
                'titulo' => $n->getTitulo(),
                'mensaje' => $n->getMensaje(),
                'fecha' => $n->getFecha()->format('Y-m-d H:i:s'),
                'leido' => $n->isLeido(),
                'tipo' => $n->getTipo(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['PATCH'])]
    public function markRead(int $id): JsonResponse
    {
        $notificacion = $this->notificationService->getNotificationById($id);
        if (!$notificacion) {
            return $this->json(['error' => 'Notificación no encontrada'], 404);
        }

        // Security Check: Ensure user owns this notification
        $user = $this->getUser();
        if (method_exists($user, 'getDomainUser')) {
            $user = $user->getDomainUser();
        }

        $isOwner = false;
        if ($user instanceof Voluntario && $notificacion->getVoluntario() && $notificacion->getVoluntario()->getDni() === $user->getDni()) {
            $isOwner = true;
        } elseif ($user instanceof Organizacion && $notificacion->getOrganizacion() && $notificacion->getOrganizacion()->getCif() === $user->getCif()) {
            $isOwner = true;
        }

        if (!$isOwner) {
            return $this->json(['error' => 'No tienes permiso para marcar esta notificación'], 403);
        }

        try {
            $this->notificationService->markAsRead($notificacion);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al marcar como leída'], 500);
        }

        return $this->json(['message' => 'Notificación marcada como leída']);
    }
}
