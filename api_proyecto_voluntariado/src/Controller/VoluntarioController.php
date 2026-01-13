<?php

namespace App\Controller;

use App\Repository\VoluntarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/voluntarios')]
class VoluntarioController extends AbstractController
{
    #[Route('', name: 'api_voluntarios_list', methods: ['GET'])]
    public function getAllVoluntarios(VoluntarioRepository $voluntarioRepository): JsonResponse
    {
        $voluntarios = $voluntarioRepository->findAll();

        $data = [];
        foreach ($voluntarios as $voluntario) {
            $data[] = [
                'dni' => $voluntario->getDni(),
                'nombre' => $voluntario->getNombre(),
                'apellido1' => $voluntario->getApellido1(),
                'apellido2' => $voluntario->getApellido2(),
                'correo' => $voluntario->getCorreo(),
                'zona' => $voluntario->getZona(),
                'fechaNacimiento' => $voluntario->getFechaNacimiento() ? $voluntario->getFechaNacimiento()->format('Y-m-d') : null,
                'experiencia' => $voluntario->getExperiencia(),
                'coche' => $voluntario->isCoche(),
                'habilidades' => $voluntario->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
                'intereses' => $voluntario->getIntereses()->map(fn($i) => ['id' => $i->getId(), 'nombre' => $i->getNombre()])->toArray(),
                'idiomas' => $voluntario->getIdiomas(),
                'estado_voluntario' => $voluntario->getEstadoVoluntario(),
                'disponibilidad' => $voluntario->getDisponibilidad(),
                'ciclo' => $voluntario->getCiclo() ? (string)$voluntario->getCiclo() : null,
                
                // INSCRIPCIONES (NUEVO)
                'inscripciones' => $voluntario->getInscripciones()->map(function($inscripcion) {
                    return [
                        'id_inscripcion' => $inscripcion->getId(),
                        'actividad' => $inscripcion->getActividad() ? $inscripcion->getActividad()->getNombre() : 'Actividad Eliminada',
                        'estado' => $inscripcion->getEstado(),
                    ];
                })->toArray(),
            ];
        }

        return $this->json($data);
    }


    #[Route('/{dni}/estado', name: 'api_voluntarios_update_estado', methods: ['PATCH'])]
    public function updateEstado(string $dni, Request $request, VoluntarioRepository $voluntarioRepository, \Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        $voluntario = $voluntarioRepository->find($dni);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if (!$nuevoEstado) {
            return $this->json(['error' => 'Falta el campo "estado"'], 400);
        }

        // Validación de estados permitidos (mismos que inscripciones u otros si definimos)
        $estadosPermitidos = ['PENDIENTE', 'ACEPTADO', 'RECHAZADO'];
        if (!in_array(strtoupper($nuevoEstado), $estadosPermitidos)) {
            return $this->json([
                'error' => 'Estado inválido',
                'permitidos' => $estadosPermitidos
            ], 400);
        }

        $voluntario->setEstadoVoluntario(strtoupper($nuevoEstado));

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el estado'], 500);
        }

        return $this->json([
            'message' => 'Estado del voluntario actualizado correctamente',
            'nuevo_estado' => $voluntario->getEstadoVoluntario()
        ]);
    }
    #[Route('/email/{email}', name: 'api_voluntarios_get_by_email', methods: ['GET'])]
    public function getByEmail(string $email, VoluntarioRepository $voluntarioRepository): JsonResponse
    {
        $voluntario = $voluntarioRepository->findOneBy(['correo' => $email]);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $data = [
            'dni' => $voluntario->getDni(),
            'nombre' => $voluntario->getNombre(),
            'apellido1' => $voluntario->getApellido1(),
            'apellido2' => $voluntario->getApellido2(),
            'correo' => $voluntario->getCorreo(),
            'zona' => $voluntario->getZona(),
            'fechaNacimiento' => $voluntario->getFechaNacimiento() ? $voluntario->getFechaNacimiento()->format('Y-m-d') : null,
            'experiencia' => $voluntario->getExperiencia(),
            'coche' => $voluntario->isCoche(),
            'habilidades' => $voluntario->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
            'intereses' => $voluntario->getIntereses()->map(fn($i) => ['id' => $i->getId(), 'nombre' => $i->getNombre()])->toArray(),
            'idiomas' => $voluntario->getIdiomas(),
            'estado_voluntario' => $voluntario->getEstadoVoluntario(),
            'disponibilidad' => $voluntario->getDisponibilidad(),
            'ciclo' => $voluntario->getCiclo() ? (string)$voluntario->getCiclo() : null
        ];

        return $this->json($data);
    }
}
