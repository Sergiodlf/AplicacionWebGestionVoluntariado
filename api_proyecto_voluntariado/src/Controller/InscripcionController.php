<?php

namespace App\Controller;

use App\Entity\Inscripcion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/inscripciones', name: 'api_inscripciones_')]
class InscripcionController extends AbstractController
{
    #[Route('/{id}/estado', name: 'update_estado', methods: ['PATCH'])]
    public function updateEstado(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $inscripcion = $em->getRepository(Inscripcion::class)->find($id);

        if (!$inscripcion) {
            return $this->json(['error' => 'Inscripción no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if (!$nuevoEstado) {
            return $this->json(['error' => 'Falta el campo "estado"'], 400);
        }

        // Validación de estados permitidos
        $estadosPermitidos = ['PENDIENTE', 'CONFIRMADO', 'RECHAZADO', 'EN_CURSO', 'FINALIZADO'];
        if (!in_array(strtoupper($nuevoEstado), $estadosPermitidos)) {
            return $this->json([
                'error' => 'Estado inválido',
                'permitidos' => $estadosPermitidos
            ], 400);
        }

        $inscripcion->setEstado(strtoupper($nuevoEstado));

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el estado'], 500);
        }

        return $this->json([
            'message' => 'Estado actualizado correctamente',
            'nuevo_estado' => $inscripcion->getEstado()
        ]);
    }
}
