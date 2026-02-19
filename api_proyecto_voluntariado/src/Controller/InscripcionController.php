<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\InscripcionService;
use App\Service\NotificationService;
use App\Service\ActivityService;
use App\Service\VolunteerService;
use App\Service\OrganizationService;

#[Route('/api/inscripciones', name: 'api_inscripciones_')]
class InscripcionController extends AbstractController
{
    use ApiErrorTrait;

    private $inscripcionService;
    private $notificationService;
    private $activityService;
    private $volunteerService;
    private $organizationService;

    public function __construct(
        InscripcionService $inscripcionService, 
        NotificationService $notificationService,
        ActivityService $activityService,
        VolunteerService $volunteerService,
        OrganizationService $organizationService
    ) {
        $this->inscripcionService = $inscripcionService;
        $this->notificationService = $notificationService;
        $this->activityService = $activityService;
        $this->volunteerService = $volunteerService;
        $this->organizationService = $organizationService;
    }

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        try {
            $estado = $request->query->get('estado');
            $inscripciones = $this->inscripcionService->getAll($estado);

            $data = [];
            foreach ($inscripciones as $inscripcion) {
                $voluntario = $inscripcion->getVoluntario();
                $actividad = $inscripcion->getActividad();

                if (!$voluntario || !$actividad) {
                    continue; // Skip corrupted records
                }

                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'dni_voluntario' => $voluntario->getDni(),
                    'nombre_voluntario' => $voluntario->getNombre() . ' ' . $voluntario->getApellido1(),
                    'email_voluntario' => $voluntario->getCorreo(),
                    'habilidades_voluntario' => $voluntario->getHabilidades(),
                    'disponibilidad_voluntario' => $voluntario->getDisponibilidad(),
                    'intereses_voluntario' => $voluntario->getIntereses(),
                    
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre_actividad' => $actividad->getNombre(),
                    'descripcion_actividad' => '', // $actividad->getDescripcion(),
                    'email_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getEmail() : '',
                    'horario' => $actividad->getHorario(),
                    'habilidades_actividad' => $actividad->getHabilidades(), // Requisitos
                    'necesidades_actividad' => $actividad->getNecesidades()->map(fn($n) => ['id' => $n->getId(), 'nombre' => $n->getNombre()])->toArray(),
                    'maxParticipantes' => $actividad->getMaxParticipantes(),
                    'fecha_inicio_actividad' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i') : null,
                    'fecha_fin_actividad' => $actividad->getFechaFin() ? $actividad->getFechaFin()->format('Y-m-d H:i') : null,
                    'fecha_inscripcion' => $inscripcion->getFechaInscripcion() ? $inscripcion->getFechaInscripcion()->format('Y-m-d H:i') : null,
                    'estado' => $inscripcion->getEstado()?->value 
                ];
            }

            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error interno en el servidor', 500);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dni = $data['dniVoluntario'] ?? null;
        $codActividad = $data['codActividad'] ?? null;

        if (!$dni || !$codActividad) {
            return $this->errorResponse('Faltan datos (dniVoluntario, codActividad)', 400);
        }

        $voluntario = $this->volunteerService->getById($dni);
        if (!$voluntario) {
            return $this->errorResponse('Voluntario no encontrado', 404);
        }

        $actividad = $this->activityService->getActivityById($codActividad);
        if (!$actividad) {
            return $this->errorResponse('Actividad no encontrada', 404);
        }

        // Verificar si ya existe la inscripción
        $existing = $this->inscripcionService->isVolunteerInscribed($actividad, $voluntario);

        // Estados que permiten re-inscripción
        $estadosReInscripcion = [
            \App\Enum\InscriptionStatus::RECHAZADO, 
            \App\Enum\InscriptionStatus::CANCELADA, 
            \App\Enum\InscriptionStatus::FINALIZADO
        ];

        if ($existing) {
            // Si está en un estado que permite reintentar, lo reactivamos (lógica abajo)
            if (!in_array($existing->getEstado(), $estadosReInscripcion)) {
                return $this->errorResponse('El voluntario ya está inscrito en esta actividad', 409, [
                    'code' => 'DUPLICATE_INSCRIPTION',
                    'id_inscripcion' => $existing->getId(),
                    'estado' => $existing->getEstado()?->value
                ]);
            }
        } else {
            // VALIDACIÓN DE CUPO MÁXIMO (Solo si es nueva inscripción)
             $ocupadas = $this->inscripcionService->countActiveInscriptions($actividad);
             if ($ocupadas >= $actividad->getMaxParticipantes()) {
                 return $this->errorResponse('El cupo máximo de participantes se ha alcanzado.', 409);
             }
        }

        
        // Auto-aceptar si es Admin o la Organización dueña
        $user = $this->getUser();
        $autoAccept = false;

        if (method_exists($user, 'getRoles') && in_array('ROLE_ADMIN', $user->getRoles())) {
            $autoAccept = true;
        }

        // Check if user is the organization owner
        if ($user instanceof Organizacion) {
            $orgActividad = $actividad->getOrganizacion();
            if ($orgActividad && $user->getCif() === $orgActividad->getCif()) {
                $autoAccept = true;
            }
        }

        try {
            $inscripcion = $this->inscripcionService->createInscription($actividad, $voluntario, $existing, $autoAccept);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear la inscripción: ' . $e->getMessage(), 500);
        }

        return $this->json([
            'message' => $existing ? 'Re-inscripción realizada correctamente' : 'Inscripción creada correctamente', 
            'estado' => $inscripcion->getEstado()?->value,
            'id_inscripcion' => $inscripcion->getId()
        ], $existing ? 200 : 201);
    }



    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function updateEstado(int $id, Request $request): JsonResponse
    {
        $inscripcion = $this->inscripcionService->getById($id);

        if (!$inscripcion) {
            return $this->errorResponse('Inscripción no encontrada', 404);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if (!$nuevoEstado) {
            return $this->errorResponse('Falta el campo "estado"', 400);
        }

        // Validación de estados permitidos
        $enumStatus = \App\Enum\InscriptionStatus::tryFrom(strtoupper($nuevoEstado));
        if (!$enumStatus) {
            return $this->errorResponse('Estado inválido', 400, ['permitidos' => array_column(\App\Enum\InscriptionStatus::cases(), 'value')]);
        }

        try {
            $this->inscripcionService->updateStatus($inscripcion, $enumStatus);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar el estado', 500);
        }

        return $this->json([
            'message' => 'Estado actualizado correctamente',
            'nuevo_estado' => $inscripcion->getEstado()?->value
        ]);
    }

    // CANCELAR INSCRIPCIÓN (ELIMINAR)
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $inscripcion = $this->inscripcionService->getById($id);

        if (!$inscripcion) {
            return $this->errorResponse('Inscripción no encontrada', 404);
        }

        // SEGURIDAD: Verificar que el usuario sea el dueño de la inscripción o un Admin
        $user = $this->getUser();
        if ($user instanceof Voluntario) {
            if ($inscripcion->getVoluntario()->getDni() !== $user->getDni()) {
                 return $this->errorResponse('No tienes permiso para eliminar esta inscripción', 403);
            }
        } elseif ($user instanceof Organizacion) {
            // Opcional: Permitir a la org eliminar inscripciones de sus actividades?
            // De momento lo restringimos
            return $this->errorResponse('Las organizaciones no pueden eliminar inscripciones directamente', 403);
        }

        try {
            $this->inscripcionService->delete($inscripcion);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la inscripción', 500);
        }

        return $this->json(['message' => 'Inscripción cancelada correctamente'], 200);
    }



    #[Route('/voluntario/{dni}/inscripciones/estado', name: 'get_inscripciones_voluntario_estado_legacy', methods: ['GET'])]
    public function getInscripcionesVoluntarioByEstadoLegacy(string $dni, Request $request): JsonResponse
    {
        return $this->getInscripcionesVoluntario($dni, $request);
    }

    #[Route('/voluntario/{dni}/inscripciones', name: 'get_inscripciones_voluntario', methods: ['GET'])]
    public function getInscripcionesVoluntario(string $dni, Request $request): JsonResponse
    {
        $voluntario = $this->volunteerService->getById($dni);

        if (!$voluntario) {
            return $this->errorResponse('Voluntario no encontrado', 404);
        }

        // Recuperar parámetro 'estado'
        $estadoInscripcion = $request->query->get('estado'); 

        $inscripciones = $this->inscripcionService->getByVoluntario($voluntario, $estadoInscripcion);

        $data = [];
        foreach ($inscripciones as $inscripcion) {
            $actividad = $inscripcion->getActividad();
            
            if ($actividad) {
                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'estado_inscripcion' => $inscripcion->getEstado()?->value,
                    
                    // Datos Actividad
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre' => $actividad->getNombre(),
                    'direccion' => $actividad->getDireccion(),
                    'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i') : null,
                    'fechaFin' => $actividad->getFechaFin() ? $actividad->getFechaFin()->format('Y-m-d H:i') : null,
                    'organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getNombre() : 'Desconocida',
                    'estado_actividad' => $actividad->getEstado(), // ActivityStatus
                    'ods' => $actividad->getOds(),
                    'habilidades' => $actividad->getHabilidades(),
                    'necesidades' => $actividad->getNecesidades()->map(fn($n) => ['id' => $n->getId(), 'nombre' => $n->getNombre()])->toArray(),
                    'maxParticipantes' => $actividad->getMaxParticipantes(),
                ];
            }
        }

        return $this->json($data);
    }

    // NUEVO ENDPOINT SMART: Mis Inscripciones (Usa Token)
    #[Route('/me', name: 'get_my_inscriptions', methods: ['GET'])]
    public function getMyInscriptions(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !($user instanceof Voluntario)) {
            return $this->errorResponse('Acceso denegado. Debes ser un voluntario.', 403);
        }

        return $this->getInscripcionesVoluntario($user->getDni(), $request);
    }

    #[Route('/organizacion/{cif}', name: 'get_inscripciones_by_organizacion', methods: ['GET'])]
    public function getInscripcionesByOrganizacion(string $cif, Request $request): JsonResponse
    {
        try {
            // 1. Check Organizacion
            $organizacion = $this->organizationService->getByCif($cif);
            if (!$organizacion) {
                return $this->errorResponse('Organización no encontrada', 404);
            }
            
            // 2. Query Repository
            $estado = $request->query->get('estado');
            
            $inscripciones = $this->inscripcionService->getByOrganizacion($cif, $estado);
            
            $data = [];
            foreach ($inscripciones as $inscripcion) {
                $voluntario = $inscripcion->getVoluntario();
                $actividad = $inscripcion->getActividad();
                
                if (!$voluntario || !$actividad) {
                    continue;
                }
    
                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'estado' => $inscripcion->getEstado()?->value,
                    'fecha_inscripcion' => $inscripcion->getFechaInscripcion() ? $inscripcion->getFechaInscripcion()->format('Y-m-d H:i') : null,
                    'voluntario' => [
                        'dni' => $voluntario->getDni(),
                    ],
                    'actividad' => [
                        'codActividad' => $actividad->getCodActividad(),
                    ]
                ];
            }
            
            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
