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
use Symfony\Component\Serializer\SerializerInterface;

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

        // 1. Validar Organización (Token o CIF explícito)
        $organizacion = null;
        $user = $this->getUser();

        // A. Prioridad: Token de Organización
        if ($user instanceof Organizacion) {
            $organizacion = $user;
        }
        // B. Fallback: CIF en el JSON (para admins o debug)
        elseif (!empty($dto->cifOrganizacion)) {
             $organizacion = $em->getRepository(Organizacion::class)->find($dto->cifOrganizacion);
        }

        if (!$organizacion) {
            return $this->json(['error' => 'Organización no identificada. Usa un Token válido o envía cifOrganizacion.'], 401);
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

                // Validar que no sea anterior a hoy
                $hoy = new \DateTime();
                $hoy->setTime(0, 0, 0);

                $fInicioCheck = clone $fInicio;
                $fInicioCheck->setTime(0, 0, 0);

                if ($fInicioCheck < $hoy) {
                    return $this->json(['error' => 'La fecha de inicio no puede ser anterior a la actual'], 400);
                }
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
        
        // Determinar estado inicial basado en fecha
        // $fInicio ya fue instanciado arriba (líneas 67 o 70)
        $now = new \DateTime();
        $estadoCalculado = 'En curso';
        if ($fInicio > $now) {
            $estadoCalculado = 'Sin comenzar';
        }


        // 4. Create Activity via Service
        try {
            $created = $this->activityService->createActivity([
                'nombre'           => $dto->nombre,
                'estado'           => $estadoCalculado, // Forzar estado explícito
                'fechaInicio'      => $dto->fechaInicio,
                'fechaFin'         => $dto->fechaFin ?? $fechaFinSql, // Use DTO or computed default
                'maxParticipantes' => $maxParticipantes,
                'direccion'        => $direccion,
                'sector'           => $dto->sector,
                'odsIds'           => $dto->ods, 
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

    // EDITAR ACTIVIDAD
    #[Route('/{id}/editar', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $actividad = $em->getRepository(Actividad::class)->find($id);

        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        // Security Check: Only the organization that owns it can edit (or admin)
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'No autorizado'], 401);
        }
        
        // If user is Organization, check ownership
        if ($user instanceof Organizacion) {
            if ($actividad->getOrganizacion() !== $user) {
                return $this->json(['error' => 'No tienes permiso para editar esta actividad'], 403);
            }
        }

        $json = $request->getContent();
        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
             return $this->json(['error' => 'JSON inválido'], 400);
        }

        try {
            // Prepare update data array
            $updateData = [
                'nombre'           => $dto->nombre,
                'fechaInicio'      => $dto->fechaInicio,
                'fechaFin'         => $dto->fechaFin,
                'maxParticipantes' => $dto->maxParticipantes,
                'direccion'        => $dto->direccion,
                'sector'           => $dto->sector,
                'odsIds'           => $dto->ods, 
                'habilidadIds'    => $dto->habilidades,
            ];

            $updatedActividad = $this->activityService->updateActivity($actividad, $updateData);

            // Re-calculate status just in case dates changed
            $this->checkAndUpdateStatus($updatedActividad);
            $em->flush();

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error actualizando actividad: ' . $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Actividad actualizada con éxito'], 200);
    }
    
    // LISTAR TODAS
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->getRepository(Actividad::class)->createQueryBuilder('a');
        
        if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
            $qb->andWhere('a.estadoAprobacion = :estadoAprobacion')
               ->setParameter('estadoAprobacion', $estadoAprobacion);
        }

        // NUEVO FILTRO INTELIGENTE: SI HAY TOKEN DE VOLUNTARIO, FILTRAR AUTOMÁTICAMENTE
        $user = $this->getUser();
        $excludeDni = $request->query->get('exclude_volunteer_dni');

        if ($user instanceof \App\Entity\Voluntario) {
            $excludeDni = $user->getDni();
        }

        if ($excludeDni) {
            $qb->andWhere('a.codActividad NOT IN (
                SELECT IDENTITY(i.actividad) 
                FROM App\Entity\Inscripcion i 
                WHERE i.voluntario = :voluntarioDni
            )')
            ->setParameter('voluntarioDni', $excludeDni);
        }

        // FILTRO POR FECHA (Ocultar pasadas por defecto)
        $mostrarHistorial = $request->query->getBoolean('history', false);
        if (!$mostrarHistorial) {
            $qb->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
               ->setParameter('now', new \DateTime());
        }

        $actividades = $qb->getQuery()->getResult();
        
        // --- ACTUALIZAR ESTADOS SEGÚN FECHA ---
        $modificado = false;
        foreach ($actividades as $actividad) {
            if ($this->checkAndUpdateStatus($actividad)) {
                $modificado = true;
            }
        }
        if ($modificado) {
            $em->flush();
        }

        $data = [];
        foreach ($actividades as $actividad) {
            $data[] = [
                'codActividad' => $actividad->getCodActividad(),
                'nombre' => $actividad->getNombre(),
                'estado' => $actividad->getEstado(),
                'estadoAprobacion' => $actividad->getEstadoAprobacion(),
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

        // SMART ENROLLMENT: USE TOKEN IF AVAILABLE
        $user = $this->getUser();
        $voluntario = null;

        if ($user instanceof \App\Entity\Voluntario) {
            $voluntario = $user;
        }

        // FALLBACK: Manual DNI in body (for Admins or potential future uses)
        if (!$voluntario) {
            $data = json_decode($request->getContent(), true);
            $dniVoluntario = $data['dni'] ?? $data['dni_voluntario'] ?? null;

            if ($dniVoluntario) {
                $voluntario = $em->getRepository(Voluntario::class)->find($dniVoluntario);
            }
        }

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado (Token inválido o falta DNI)'], 404);
        }

        // 0. Comprobar si la actividad está completada o finalizada
        // Asumiendo que 'FINALIZADO', 'COMPLETADA', 'CERRADA' son estados finales.
        // También podríamos verificar la fechaFin < hoy
        $estadoActividad = strtoupper($actividad->getEstado());
        $estadosFinales = ['FINALIZADO', 'COMPLETADA', 'CERRADA', 'CANCELADO'];
        $now = new \DateTime();
        
        if (in_array($estadoActividad, $estadosFinales) || ($actividad->getFechaFin() && $actividad->getFechaFin() < $now)) {
             return $this->json(['error' => 'La actividad ya está completada o finalizada'], 409);
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

    // DESINSCRIBIR VOLUNTARIO (Smart Unsubscribe via Activity ID)
    #[Route('/{id}/desinscribir', name: 'desinscribir', methods: ['DELETE'])]
    public function desinscribir(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !($user instanceof Voluntario)) {
             return $this->json(['error' => 'Acceso denegado. Debes ser un voluntario.'], 403);
        }

        $actividad = $em->getRepository(Actividad::class)->find($id);
        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        // Buscar la inscripción de ESTE voluntario en ESTA actividad
        $inscripcion = $em->getRepository(\App\Entity\Inscripcion::class)->findOneBy([
            'voluntario' => $user,
            'actividad' => $actividad
        ]);

        if (!$inscripcion) {
            return $this->json(['error' => 'No estás inscrito en esta actividad'], 404);
        }

        // Reglas de negocio (Opcional: No permitir si ya acabó)
        // if ($inscripcion->getEstado() === 'FINALIZADO') ...

        try {
            $em->remove($inscripcion);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al desinscribir: ' . $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Te has desapuntado correctamente.'], 200);
    }

    // OBTENER MIS ACTIVIDADES (Organización vía Token)
    #[Route('/mis-actividades', name: 'my_activities', methods: ['GET'])]
    public function getMisActividades(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !($user instanceof Organizacion)) {
            return $this->json(['error' => 'Acceso denegado. Debes iniciar sesión como Organización.'], 403);
        }

        // Reutilizar lógica de búsqueda
        $criteria = ['organizacion' => $user];

        if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
            $criteria['estadoAprobacion'] = $estadoAprobacion;
        }
        if ($estado = $request->query->get('estado')) {
            $criteria['estado'] = $estado;
        }

        $actividades = $em->getRepository(Actividad::class)->findBy($criteria, ['fechaInicio' => 'DESC']);

        // --- ACTUALIZAR ESTADOS SEGÚN FECHA ---
        $modificado = false;
        foreach ($actividades as $actividad) {
            if ($this->checkAndUpdateStatus($actividad)) {
                $modificado = true;
            }
        }
        if ($modificado) {
            $em->flush();
        }

        $data = [];
        foreach ($actividades as $actividad) {
            $data[] = [
                'codActividad' => $actividad->getCodActividad(),
                'nombre' => $actividad->getNombre(),
                'estado' => $actividad->getEstado(),
                'estadoAprobacion' => $actividad->getEstadoAprobacion(),
                'direccion' => $actividad->getDireccion(),
                'sector'    => $actividad->getSector(),
                'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i:s') : null,
                'maxParticipantes' => $actividad->getMaxParticipantes(),
                'ods'              => $actividad->getOds()->map(fn($o) => ['id' => $o->getId(), 'nombre' => $o->getNombre(), 'color' => $o->getColor()])->toArray(),
                'habilidades'      => $actividad->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
            ];
        }

        return $this->json($data);
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
            // 2. Estado de aprobación del organizador (ACEPTADA, PENDIENTE, RECHAZADA)
            if ($estadoAprobacion = $request->query->get('estadoAprobacion')) {
                $criteria['estadoAprobacion'] = $estadoAprobacion;
            }
            // Agregado soporte para filtrar por estado de ejecución (ABIERTA, CERRADA, etc.)
            if ($estado = $request->query->get('estado')) {
                $criteria['estado'] = $estado;
            }

            $actividades = $em->getRepository(Actividad::class)->findBy($criteria);

            // --- ACTUALIZAR ESTADOS SEGÚN FECHA ---
            $modificado = false;
            foreach ($actividades as $actividad) {
                if ($this->checkAndUpdateStatus($actividad)) {
                    $modificado = true;
                }
            }
            if ($modificado) {
                $em->flush();
            }

            $data = [];
            foreach ($actividades as $actividad) {
                // Mapeo básico de actividad
                $data[] = [
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre' => $actividad->getNombre(),
                    'estado' => $actividad->getEstado(),
                    'estadoAprobacion' => $actividad->getEstadoAprobacion(),
                    'direccion' => $actividad->getDireccion(),
                    'sector'    => $actividad->getSector(),
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
        // Nuevo campo opcional para desambiguar 'PENDIENTE'
        $tipo = $data['tipo'] ?? null; // 'aprobacion' o 'ejecucion'

        if (!$nuevoEstado) {
            return $this->json(['error' => 'Falta el campo "estado"'], 400);
        }

        $nuevoEstadoUpper = strtoupper($nuevoEstado); // Normalizamos input
        
        // --- DEFINICIÓN DE LISTAS BLANCAS ---
        $estadosAprobacion = ['ACEPTADA', 'RECHAZADA', 'PENDIENTE'];
        
        // Mapa de normalización para ejecución: UPPER -> StoredValue
        $mapaEjecucion = [
            'SIN COMENZAR' => 'Sin comenzar',
            'EN CURSO'     => 'En curso',
            'COMPLETADA'   => 'Completada',
            'COMPLETADO'   => 'Completada',
            'FINALIZADO'   => 'Completada', 
            'CANCELADO'    => 'CANCELADO',
            'ABIERTA'      => 'En curso',
            'PENDIENTE'    => 'Sin comenzar' 
        ];

        // --- LÓGICA INTELIGENTE DE ASIGNACIÓN ---
        
        // Caso 1: Forzado explícitamente por el cliente
        if ($tipo === 'aprobacion') {
            if (!in_array($nuevoEstadoUpper, $estadosAprobacion)) {
                return $this->json(['error' => "Estado de aprobación inválido: $nuevoEstadoUpper"], 400);
            }
            $actividad->setEstadoAprobacion($nuevoEstadoUpper);
            $campoActualizado = 'estadoAprobacion';

        } elseif ($tipo === 'ejecucion') {
            if (!array_key_exists($nuevoEstadoUpper, $mapaEjecucion)) {
                 return $this->json(['error' => "Estado de ejecución inválido: $nuevoEstadoUpper"], 400);
            }
            $actividad->setEstado($mapaEjecucion[$nuevoEstadoUpper]);
            $campoActualizado = 'estado';

        } else {
            // Caso 2: Auto-detección (Sin 'tipo')
            // Prioridad a valores únicos de Aprobación
            if (in_array($nuevoEstadoUpper, ['ACEPTADA', 'RECHAZADA'])) {
                $actividad->setEstadoAprobacion($nuevoEstadoUpper);
                $campoActualizado = 'estadoAprobacion';
                
            } elseif (array_key_exists($nuevoEstadoUpper, $mapaEjecucion)) {
                $actividad->setEstado($mapaEjecucion[$nuevoEstadoUpper]);
                $campoActualizado = 'estado';

            } else {
                 return $this->json([
                    'error' => 'Estado desconocido o inválido', 
                    'valor' => $nuevoEstadoUpper
                ], 400);
            }
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el estado: ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => 'Estado actualizado correctamente',
            'campo_actualizado' => $campoActualizado,
            'valor_nuevo' => ($campoActualizado === 'estado') ? $actividad->getEstado() : $actividad->getEstadoAprobacion()
        ]);
    }

    // MÉTODO PRIVADO PARA CHEQUEAR Y ACTUALIZAR ESTADO SEGÚN FECHAS
    private function checkAndUpdateStatus(Actividad $actividad): bool
    {
        $now = new \DateTime();
        $start = $actividad->getFechaInicio();
        $end = $actividad->getFechaFin();
        $estadoActual = $actividad->getEstado();
        $nuevoEstado = null;

        // Reglas de negocio
        if ($start && $now < $start) {
            $nuevoEstado = 'Sin comenzar';
        } elseif ($end && $now > $end) {
            $nuevoEstado = 'Completada';
        } else {
            // Si ya empezó y no ha terminado (o no tiene fin), está en curso
            $nuevoEstado = 'En curso';
        }


        // Solo actualizamos si cambia
        if ($nuevoEstado && $estadoActual !== $nuevoEstado) {
            $actividad->setEstado($nuevoEstado);
            return true;
        }

        return false;
    }
}