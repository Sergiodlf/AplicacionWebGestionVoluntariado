<?php

namespace App\Controller;

use App\Service\VolunteerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/voluntarios')]
class VoluntarioController extends AbstractController
{
    private $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    #[Route('', name: 'api_voluntarios_list', methods: ['GET'])]
    public function getAllVoluntarios(Request $request): JsonResponse
    {
        $criteria = [];
        if ($estado = $request->query->get('estado')) {
            $criteria['estadoVoluntario'] = $estado;
        }

        $voluntarios = $this->volunteerService->getAll($criteria);

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
                'estado_voluntario' => $voluntario->getEstadoVoluntario()?->value,
                'disponibilidad' => $voluntario->getDisponibilidad(),
                'ciclo' => $voluntario->getCiclo() ? (string)$voluntario->getCiclo() : null,
                
                // INSCRIPCIONES (NUEVO)
                'inscripciones' => $voluntario->getInscripciones()->map(function($inscripcion) {
                    return [
                        'id_inscripcion' => $inscripcion->getId(),
                        'actividad' => $inscripcion->getActividad() ? $inscripcion->getActividad()->getNombre() : 'Actividad Eliminada',
                        'estado' => $inscripcion->getEstado()?->value,
                    ];
                })->toArray(),
            ];
        }

        return $this->json($data);
    }


    #[Route('/{dni}', name: 'api_voluntarios_patch', methods: ['PATCH'])]
    public function updatePartial(string $dni, Request $request): JsonResponse
    {
        $voluntario = $this->volunteerService->getById($dni);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        // 1. Actualizar Estado
        if (isset($data['estado'])) {
             $nuevoEstado = $data['estado'];
             $enumStatus = \App\Enum\VolunteerStatus::tryFrom($nuevoEstado);
             
             if (!$enumStatus) {
                return $this->json([
                    'error' => 'Estado inválido',
                    'permitidos' => array_column(\App\Enum\VolunteerStatus::cases(), 'value')
                ], 400);
            }
            $this->volunteerService->updateStatus($voluntario, $enumStatus);
        }

        return $this->json([
            'message' => 'Voluntario actualizado correctamente',
            'estado' => $voluntario->getEstadoVoluntario()?->value
        ]);
    }

    #[Route('/email/{email}', name: 'api_voluntarios_get_by_email', methods: ['GET'])]
    public function getByEmail(string $email): JsonResponse
    {
        $voluntario = $this->volunteerService->getByEmail($email);

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
            'estado_voluntario' => $voluntario->getEstadoVoluntario()?->value,
            'disponibilidad' => $voluntario->getDisponibilidad(),
            'ciclo' => $voluntario->getCiclo() ? (string)$voluntario->getCiclo() : null
        ];

        return $this->json($data);
    }

    #[Route('/{dni}', name: 'api_voluntarios_update', methods: ['PUT'])]
    public function update(string $dni, Request $request): JsonResponse
    {
        $voluntario = $this->volunteerService->getById($dni);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $this->volunteerService->updateProfile($voluntario, $data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el perfil: ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => 'Perfil actualizado correctamente',
            'voluntario' => [
                'dni' => $voluntario->getDni(),
                'nombre' => $voluntario->getNombre(),
                'zona' => $voluntario->getZona(),
                'habilidades' => $voluntario->getHabilidades(),
                'intereses' => $voluntario->getIntereses(),
                'disponibilidad' => $voluntario->getDisponibilidad()
            ]
        ]);
    }
}
