<?php

namespace App\Controller;

use App\Service\NotificationService;
use App\Entity\Voluntario;
use App\Entity\Organizacion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notificaciones', name: 'api_notificaciones_')]
class NotificationController extends AbstractController
{
    use ApiErrorTrait;

    private NotificationManagerInterface $notificationManager;

    public function __construct(NotificationManagerInterface $notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', 401);
        }

        // Ensure we have the domain user entity
        if (method_exists($user, 'getDomainUser')) {
            $user = $user->getDomainUser();
        }

        $notificaciones = $this->notificationManager->getNotificationsForUser($user);

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

    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $notificacion = $this->notificationManager->getNotificationById($id);
        if (!$notificacion) {
            return $this->errorResponse('Notificación no encontrada', 404);
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
            return $this->errorResponse('No tienes permiso para marcar esta notificación', 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['leido']) && $data['leido'] === true) {
            try {
                $this->notificationManager->markAsRead($notificacion);
            } catch (\Exception $e) {
                return $this->errorResponse('Error al marcar como leída', 500);
            }
        }

        return $this->json(['message' => 'Notificación actualizada correctamente']);
    }
}
