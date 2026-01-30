<?php

namespace App\Controller;

use App\Repository\NotificacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Voluntario;
use App\Entity\Organizacion;

#[Route('/api/notificaciones', name: 'api_notificaciones_')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAll(NotificacionRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        $notificaciones = [];
        if ($user instanceof Voluntario) {
            $notificaciones = $repo->findByVoluntario($user->getDni());
        } elseif ($user instanceof Organizacion) {
            $notificaciones = $repo->findByOrganizacion($user->getCif());
        }

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
    public function markRead(int $id, NotificacionRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $notificacion = $repo->find($id);
        if (!$notificacion) {
            return $this->json(['error' => 'Notificación no encontrada'], 404);
        }

        // Security Check: Ensure user owns this notification
        $user = $this->getUser();
        $isOwner = false;
        if ($user instanceof Voluntario && $notificacion->getVoluntario()->getDni() === $user->getDni()) {
            $isOwner = true;
        } elseif ($user instanceof Organizacion && $notificacion->getOrganizacion()->getCif() === $user->getCif()) {
            $isOwner = true;
        }

        if (!$isOwner) {
            return $this->json(['error' => 'No tienes permiso para marcar esta notificación'], 403);
        }

        $notificacion->setLeido(true);
        $em->flush();

        return $this->json(['message' => 'Notificación marcada como leída']);
    }
}
