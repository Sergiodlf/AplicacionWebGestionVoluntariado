<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Model\CrearActividadDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ActivityService;
use App\Service\InscripcionService;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/actividades', name: 'api_actividades_')]
class ActividadController extends AbstractController
{
    use ApiErrorTrait;

    private $activityService;
    private $inscripcionService;
    private $organizationService;

    public function __construct(
        ActivityService $activityService, 
        InscripcionService $inscripcionService,
        \App\Service\OrganizationService $organizationService
    ) {
        $this->activityService = $activityService;
        $this->inscripcionService = $inscripcionService;
        $this->organizationService = $organizationService;
    }
    // CREAR ACTIVIDAD

    #[Route('/crear', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->errorResponse('JSON inválido', 400);
        }

        // 1. Validar Organización
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();
        
        $organizacion = ($user instanceof Organizacion) ? $user : null;
        if (!$organizacion && !empty($dto->cifOrganizacion)) {
             $organizacion = $this->organizationService->getByCif($dto->cifOrganizacion);
        }

        if (!$organizacion) {
            return $this->errorResponse('Organización no identificada. Usa un Token válido o envía cifOrganizacion.', 401);
        }

        // CONTROL DE DUPLICIDAD
        if ($this->activityService->activityExists($dto->nombre, $organizacion)) {
            return $this->errorResponse('Ya existe una actividad con este nombre en tu organización', 409);
        }

        try {
            // S-3: Delegate creation to service (L-2 Enums handled internally)
            $estadoAprobacion = (($user instanceof \App\Entity\Administrador) || 
                                ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles()))) 
                                ? \App\Enum\ActivityApproval::ACEPTADA 
                                : \App\Enum\ActivityApproval::PENDIENTE;

            $created = $this->activityService->createActivity([
                'nombre'           => $dto->nombre,
                'descripcion'      => $dto->descripcion, 
                'estadoAprobacion' => $estadoAprobacion, 
                'fechaInicio'      => $dto->fechaInicio,   
                'fechaFin'         => $dto->fechaFin,      
                'maxParticipantes' => $dto->maxParticipantes,
                'direccion'        => $dto->direccion,
                'sector'           => $dto->sector,
                'odsIds'           => $dto->ods, 
                'habilidadIds'    => $dto->habilidades,
            ], $organizacion);

            if (!$created) {
                return $this->errorResponse('No se pudo crear la actividad', 500);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear la actividad: ' . $e->getMessage(), 400);
        }

        return $this->json(['message' => 'Actividad creada con éxito'], 201);
    }

    // EDITAR ACTIVIDAD
    #[Route('/{id}/editar', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $actividad = $this->activityService->getActivityById($id);

        if (!$actividad) {
            return $this->errorResponse('Actividad no encontrada', 404);
        }

        // Validar permisos
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if (!$user) {
            return $this->errorResponse('No autorizado', 401);
        }
        
        if ($user instanceof Organizacion && $actividad->getOrganizacion() !== $user) {
            return $this->errorResponse('No tienes permiso para editar esta actividad', 403);
        }

        $json = $request->getContent();
        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
             return $this->errorResponse('JSON inválido', 400);
        }

        try {
            // S-3: Delegate update to service
            $this->activityService->updateActivity($actividad, [
                'nombre'           => $dto->nombre,
                'descripcion'      => $dto->descripcion,
                'fechaInicio'      => $dto->fechaInicio,
                'fechaFin'         => $dto->fechaFin,
                'maxParticipantes' => $dto->maxParticipantes,
                'direccion'        => $dto->direccion,
                'sector'           => $dto->sector,
                'odsIds'           => $dto->ods, 
                'habilidadIds'    => $dto->habilidades,
            ]);

            // Recalcular estado
            $this->activityService->checkAndUpdateStatus($actividad);
            $this->activityService->flush();

        } catch (\Exception $e) {
            return $this->errorResponse('Error actualizando actividad: ' . $e->getMessage(), 400);
        }

        return $this->json(['message' => 'Actividad actualizada con éxito'], 200);
    }

    // LISTAR TODAS
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = [];
        
        // Filtro por Estado de Aprobación
        $estadoParam = $request->query->get('estadoAprobacion');
        if ($estadoParam && strtoupper($estadoParam) !== 'ALL') {
            $filters['estadoAprobacion'] = $estadoParam;
        }

        // Filtro por Estado de Ejecución
        $filters['estado'] = $request->query->get('estado') ?? 'NOT_CANCELLED';

        // Filtro inteligente (excluir propias inscripciones si es voluntario)
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if ($user instanceof \App\Entity\Voluntario) {
            $filters['exclude_volunteer_dni'] = $user->getDni();
        } elseif ($excludeDni = $request->query->get('exclude_volunteer_dni')) {
             $filters['exclude_volunteer_dni'] = $excludeDni;
        }

        // Filtro Histórico
        $filters['history'] = $request->query->getBoolean('history', false);

        $actividades = $this->activityService->getActivitiesByFilters($filters);
        
        // Actualizar estados
        $modificado = false;
        foreach ($actividades as $actividad) {
            if ($this->activityService->checkAndUpdateStatus($actividad)) {
                $modificado = true;
            }
        }
        if ($modificado) {
            $this->activityService->flush();
        }

        // Transform to Array
        $data = $this->transformActivitiesToArray($actividades);

        return $this->json($data);
    }

    // OBTENER MIS ACTIVIDADES (Organización vía Token)
    #[Route('/mis-actividades', name: 'my_activities', methods: ['GET'])]
    public function getMisActividades(Request $request): JsonResponse
    {
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if (!$user || !($user instanceof Organizacion)) {
            return $this->errorResponse('Acceso denegado. Debes iniciar sesión como Organización.', 403);
        }

        $filters = ['organizacion' => $user];

        // Filtro por Estado de Aprobación
        if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
            $filters['estadoAprobacion'] = $estadoAprobacion;
        }

        // Filtro por Estado de Ejecución
        $estado = $request->query->get('estado');
        if ($estado) {
            if (strtolower($estado) === 'pendiente') {
                $filters['estadoAprobacion'] = 'PENDIENTE';
                $filters['estado'] = 'NOT_CANCELLED'; 
            } else {
                 $filters['estado'] = $estado;
            }
        } else {
            $filters['estado'] = 'NOT_CANCELLED';
        }

        $actividades = $this->activityService->getActivitiesByFilters($filters);

        // Actualizar estados
        $modificado = false;
        foreach ($actividades as $actividad) {
            if ($this->activityService->checkAndUpdateStatus($actividad)) {
                $modificado = true;
            }
        }
        if ($modificado) {
            $this->activityService->flush();
        }

        return $this->json($this->transformActivitiesToArray($actividades));
    }

    // OBTENER ACTIVIDADES POR ORGANIZACIÓN (Con Inscripciones)
    #[Route('/organizacion/{cif}', name: 'get_by_organizacion', methods: ['GET'])]
    public function getByOrganizacion(string $cif, Request $request): JsonResponse
    {
        try {
            $organizacion = $this->organizationService->getByCif($cif);
            if (!$organizacion) {
                return $this->errorResponse('Organización no encontrada', 404);
            }

            $filters = ['organizacion' => $organizacion];
            if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
                $filters['estadoAprobacion'] = $estadoAprobacion;
            }
            if ($estado = $request->query->get('estado')) {
                $filters['estado'] = $estado;
            }

            // Historial (Crucial para ver actividades finalizadas)
            $filters['history'] = $request->query->getBoolean('history', false);

            $actividades = $this->activityService->getActivitiesByFilters($filters);

            // Actualizar estados
            $modificado = false;
            foreach ($actividades as $actividad) {
                 if ($this->activityService->checkAndUpdateStatus($actividad)) {
                    $modificado = true;
                }
            }
            if ($modificado) {
                $this->activityService->flush();
            }

            return $this->json($this->transformActivitiesToArray($actividades));
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    // INSCRIBIRSE EN ACTIVIDAD
    #[Route('/{id}/inscribir', name: 'inscribir', methods: ['POST', 'DELETE'])]
    public function inscribir(int $id, Request $request): JsonResponse
    {
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if (!$user || !($user instanceof \App\Entity\Voluntario)) {
            return $this->errorResponse('Acceso denegado. Solo voluntarios.', 403);
        }

        $actividad = $this->activityService->getActivityById($id);
        if (!$actividad) {
            return $this->errorResponse('Actividad no encontrada', 404);
        }

        // Desinscribir (DELETE)
        if ($request->getMethod() === 'DELETE') {
            $inscripcion = $this->inscripcionService->isVolunteerInscribed($actividad, $user);
            if (!$inscripcion) {
                return $this->errorResponse('No estás inscrito en esta actividad', 404);
            }
            
            try {
                $this->inscripcionService->delete($inscripcion);
                return $this->json(['message' => 'Te has desinscrito correctamente'], 200);
            } catch (\Exception $e) {
                return $this->errorResponse('Error al desinscribir', 500);
            }
        }

        // Inscripción (POST) - Validar duplicidad
        $existing = $this->inscripcionService->isVolunteerInscribed($actividad, $user);
        if ($existing) {
             $estado = $existing->getEstado();
             if ($estado !== \App\Enum\InscriptionStatus::CANCELADA && 
                 $estado !== \App\Enum\InscriptionStatus::RECHAZADO &&
                 $estado !== \App\Enum\InscriptionStatus::FINALIZADO) {
                 return $this->errorResponse('Ya estás inscrito en esta actividad', 409);
             }
        }

        // Validar Cupo
        if (!$existing || $existing->getEstado() === \App\Enum\InscriptionStatus::CANCELADA) {
            $ocupadas = $this->inscripcionService->countActiveInscriptions($actividad);
            if ($ocupadas >= $actividad->getMaxParticipantes()) {
                return $this->errorResponse('El cupo máximo de participantes se ha alcanzado.', 409);
            }
        }

        try {
            $autoAccept = false;
            $inscripcion = $this->inscripcionService->createInscription($actividad, $user, $existing, $autoAccept);

            return $this->json([
                'message' => 'Inscripción realizada con éxito',
                'estado' => $inscripcion->getEstado()->value,
                'id_inscripcion' => $inscripcion->getId()
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al realizar la inscripción: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper to transform entity array to JSON array
     */
    private function transformActivitiesToArray(array $actividades): array
    {
        $data = [];
        foreach ($actividades as $actividad) {
            $data[] = [
                'codActividad' => $actividad->getCodActividad(),
                'nombre' => $actividad->getNombre(),
                'descripcion' => $actividad->getDescripcion(),
                'estado' => $actividad->getEstado() ? $actividad->getEstado()->value : null,
                'estadoAprobacion' => $actividad->getEstadoAprobacion() ? $actividad->getEstadoAprobacion()->value : null,
                'direccion' => $actividad->getDireccion(),
                'sector'    => $actividad->getSector(),
                'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i:s') : null,
                'fechaFin' => $actividad->getFechaFin() ? $actividad->getFechaFin()->format('Y-m-d H:i:s') : null,
                'maxParticipantes' => $actividad->getMaxParticipantes(),
                'ods'              => $actividad->getOds()->map(fn($o) => ['id' => $o->getId(), 'nombre' => $o->getNombre(), 'color' => $o->getColor()])->toArray(),
                'habilidades'      => $actividad->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
                'necesidades'      => $actividad->getNecesidades()->map(fn($n) => ['id' => $n->getId(), 'nombre' => $n->getNombre()])->toArray(),
                'nombre_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getNombre() : 'Organización Desconocida',
                'cif_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getCif() : null,
            ];
        }
        return $data;
    }

    // ACTUALIZAR ESTADO DE ACTIVIDAD
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function updateEstado(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;
        $tipo = $data['tipo'] ?? null;

        if (!$nuevoEstado) {
            return $this->errorResponse('Falta el campo "estado"', 400);
        }

        try {
            $result = $this->activityService->updateActivityStatus($id, $nuevoEstado, $tipo);
            
            $jsonResponse = [
                'message' => 'Estado actualizado correctamente',
                'campo_actualizado' => $result['campo_actualizado'],
                'valor_nuevo' => $result['valor_nuevo']
            ];

            return $this->json($jsonResponse);

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $code = 500;
            if ($e->getMessage() === 'Actividad no encontrada') {
                $code = 404;
            }
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    // ELIMINAR O CANCELAR ACTIVIDAD
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $action = $this->activityService->deleteActivity($id);
            
            $message = ($action === 'cancelled') 
                ? 'Actividad cancelada porque tenía inscripciones.'
                : 'Actividad eliminada permanentemente.';
            
            $jsonResponse = [
                'message' => $message,
                'action' => $action
            ];
            
            return $this->json($jsonResponse, 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}