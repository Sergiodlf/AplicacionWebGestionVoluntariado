<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Model\CrearActividadDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ActivityService;
use App\Service\InscripcionService;

#[Route('/api/actividades', name: 'api_actividades_')]
class ActividadController extends AbstractController
{
    private $activityService;
    private $inscripcionService;

    public function __construct(ActivityService $activityService, InscripcionService $inscripcionService)
    {
        $this->activityService = $activityService;
        $this->inscripcionService = $inscripcionService;
    }
    // =========================================================================
    // CREAR ACTIVIDAD (SOLUCIÓN BLINDADA: SQL PURO)
    // =========================================================================
    #[Route('/crear', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        // 1. Validar Organización
        $organizacion = $em->getRepository(Organizacion::class)->find($dto->cifOrganizacion);
        if (!$organizacion) {
            return $this->json(['error' => 'Organización no encontrada. Revisa el CIF.'], 404);
        }

        // --- CONTROL DE DUPLICIDAD (PV-40) ---
        if ($this->activityService->activityExists($dto->nombre, $organizacion)) {
            return $this->json(['error' => 'Ya existe una actividad con este nombre en tu organización'], 409);
        }

        // 2. Preparar Fechas (FORMATO Ymd PARA SQL SERVER)
        // Esto es lo que evita el error SQLSTATE [22007, 241]
        $fechaInicioSql = null;
        $fechaFinSql = null;

        try {
            // Fecha Inicio
            if ($dto->fechaInicio) {
                $fInicio = new \DateTime($dto->fechaInicio);
                $fechaInicioSql = $fInicio->format('Ymd'); // Ej: 20251201
            } else {
                $fInicio = new \DateTime();
                $fechaInicioSql = $fInicio->format('Ymd');
            }

            // Fecha Fin
            if (!empty($dto->fechaFin)) {
                $fFin = new \DateTime($dto->fechaFin);
                $fechaFinSql = $fFin->format('Ymd');
            } else {
                // Por defecto +30 días
                $fFin = (new \DateTime())->modify('+30 days');
                $fechaFinSql = $fFin->format('Ymd');
            }

            // Validación extra de lógica (Fin > Inicio)
            if ($fFin < $fInicio) {
                return $this->json(['error' => 'La fecha de fin no puede ser anterior a la de inicio'], 400);
            }

        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato de fecha inválido. Usa AAAA-MM-DD'], 400);
        }

        // 3. Preparar otros datos
        $maxParticipantes = $dto->maxParticipantes ?? 10;

        // --- VALIDACIÓN DE CUPO MÁXIMO (PV-39) ---
        if ($maxParticipantes <= 0) {
            return $this->json(['error' => 'El cupo máximo de participantes debe ser mayor que cero'], 400);
        }

        $direccion = $dto->direccion ?? 'Sede Principal';
        $estado = 'En Curso';

        // 4. Create Activity via Service
        try {
            $created = $this->activityService->createActivity([
                'nombre'           => $dto->nombre,
                'fechaInicio'      => $dto->fechaInicio,
                'fechaFin'         => $dto->fechaFin,
                'maxParticipantes' => $maxParticipantes,
                'direccion'        => $direccion,
                'odsIds'           => $dto->ods, // Now expected to be IDs from frontend
                'habilidadIds'    => $dto->habilidades,
                'necesidadIds'    => [] // Extend DTO if needed later
            ], $organizacion);

            if (!$created) {
                return $this->json(['error' => 'No se pudo crear la actividad'], 500);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Actividad creada con éxito'], 201);
    }
    
    // LISTAR TODAS
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $actividades = $em->getRepository(Actividad::class)->findAll();
        
        $data = [];
        foreach ($actividades as $actividad) {
            $data[] = [
                'codActividad' => $actividad->getCodActividad(),
                'nombre' => $actividad->getNombre(),
                'nombre' => $actividad->getNombre(),
                'estado' => $actividad->getEstado(),
                'estadoAprobacion' => $actividad->getEstadoAprobacion(),
                'direccion' => $actividad->getDireccion(),
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

        return $this->json($data);
    }

    // INSCRIBIR VOLUNTARIO (Actualizado para usar la entidad Inscripcion)
    #[Route('/{id}/inscribir', name: 'inscribir', methods: ['POST'])]
    public function inscribir(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $actividad = $em->getRepository(Actividad::class)->find($id);

        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $dniVoluntario = $data['dni'] ?? $data['dni_voluntario'] ?? null;

        if (!$dniVoluntario) {
            return $this->json(['error' => 'Falta el campo "dni"'], 400);
        }

        $voluntario = $em->getRepository(Voluntario::class)->find($dniVoluntario);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        // 1. Comprobar si ya existe la inscripción (Vía Service)
        if ($this->inscripcionService->isVolunteerInscribed($actividad, $voluntario)) {
            return $this->json(['error' => 'El voluntario ya está inscrito en esta actividad'], 409);
        }

        // 2. VALIDACIÓN DE CUPO MÁXIMO (Vía Service)
        $ocupadas = $this->inscripcionService->countActiveInscriptions($actividad);

        if ($ocupadas >= $actividad->getMaxParticipantes()) {
            return $this->json([
                'error' => 'El cupo máximo de participantes se ha alcanzado.',
                'maximo' => $actividad->getMaxParticipantes(),
                'ocupadas' => $ocupadas
            ], 409); 
        }

        // 3. Crear nueva inscripción (Vía Service)
        try {
            $this->inscripcionService->createInscription($actividad, $voluntario);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error al inscribir voluntario',
                'mensaje_tecnico' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'Solicitud de inscripción enviada con estado PENDIENTE'], 201);
    }

    // OBTENER ACTIVIDADES POR ORGANIZACIÓN (Con Inscripciones)
    #[Route('/organizacion/{cif}', name: 'get_by_organizacion', methods: ['GET'])]
    public function getByOrganizacion(string $cif, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $organizacion = $em->getRepository(Organizacion::class)->find($cif);
            if (!$organizacion) {
                return $this->json(['error' => 'Organización no encontrada'], 404);
            }

            $criteria = ['organizacion' => $organizacion];

            // Filtros opcionales de Actividad
            // 1. Estado de ejecución (Pendiente, En Curso, Finalizado...)
            if ($estado = $request->query->get('estado')) {
                $criteria['estado'] = $estado;
            }
            // 2. Estado de aprobación del organizador (ACEPTADA, PENDIENTE, RECHAZADA)
            if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
                $criteria['estadoAprobacion'] = $estadoAprobacion;
            }

            $actividades = $em->getRepository(Actividad::class)->findBy($criteria);

            $data = [];
            foreach ($actividades as $actividad) {
                // Mapeo básico de actividad
                $data[] = [
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre' => $actividad->getNombre(),
                    'estado' => $actividad->getEstado(),
                    'estadoAprobacion' => $actividad->getEstadoAprobacion(),
                    'direccion' => $actividad->getDireccion(),
                    'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i:s') : null,
                    'maxParticipantes' => $actividad->getMaxParticipantes(),
                    'ods'              => $actividad->getOds()->map(fn($o) => ['id' => $o->getId(), 'nombre' => $o->getNombre(), 'color' => $o->getColor()])->toArray(),
                    'habilidades'      => $actividad->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
                ];
            }

            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
    // ACTUALIZAR ESTADO DE ACTIVIDAD
    #[Route('/{id}/estado', name: 'update_estado', methods: ['PATCH'])]
    public function updateEstado(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $actividad = $em->getRepository(Actividad::class)->find($id);

        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if (!$nuevoEstado) {
            return $this->json(['error' => 'Falta el campo "estado"'], 400);
        }

        // Normalizar entrada
        $nuevoEstadoUpper = strtoupper($nuevoEstado);
        $estadosPermitidos = ['PENDIENTE', 'EN CURSO', 'FINALIZADO', 'CANCELADO', 'ACEPTADA', 'ABIERTA'];

        if (!in_array($nuevoEstadoUpper, $estadosPermitidos)) {
            return $this->json([
                'error' => 'Estado inválido',
                'permitidos' => $estadosPermitidos
            ], 400);
        }
        
        $mapaFormato = [
            'PENDIENTE' => 'PENDIENTE',
            'EN CURSO' => 'EN CURSO',
            'FINALIZADO' => 'FINALIZADO',
            'CANCELADO' => 'CANCELADO',
            'ACEPTADA' => 'ACEPTADA',
            'ABIERTA' => 'ABIERTA'
        ];
        
        $estadoGuardar = $mapaFormato[$nuevoEstadoUpper] ?? $nuevoEstado;

        $actividad->setEstado($estadoGuardar);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el estado: ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => 'Estado actualizado correctamente',
            'nuevo_estado' => $actividad->getEstado()
        ]);
    }
}