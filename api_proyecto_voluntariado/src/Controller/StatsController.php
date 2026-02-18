<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VolunteerService;
use App\Service\OrganizationService;
use App\Service\ActivityService;
use App\Service\InscripcionService;

#[Route('/api/stats', name: 'api_stats_')]
class StatsController extends AbstractController
{
    private $volunteerService;
    private $organizationService;
    private $activityService;
    private $inscripcionService;

    public function __construct(
        VolunteerService $volunteerService,
        OrganizationService $organizationService,
        ActivityService $activityService,
        InscripcionService $inscripcionService
    ) {
        $this->volunteerService = $volunteerService;
        $this->organizationService = $organizationService;
        $this->activityService = $activityService;
        $this->inscripcionService = $inscripcionService;
    }

    #[Route('/general', name: 'general', methods: ['GET'])]
    public function getGeneralStats(): JsonResponse
    {
        // 2. VOLUNTARIOS (Solo activos - ACEPTADO)
        $totalVoluntarios = $this->volunteerService->countByStatus('ACEPTADO');
        $voluntariosPendientes = $this->volunteerService->countByStatus('PENDIENTE');

        // 3. ORGANIZACIONES (Solo activas - ACEPTADA)
        $totalOrganizaciones = $this->organizationService->countByStatus('ACEPTADA');
        $organizacionesPendientes = $this->organizationService->countByStatus('PENDIENTE');

        // 4. ACTIVIDADES (PROJECTS)
        $totalActividades = $this->activityService->countVisible();
        $actividadesPendientes = $this->activityService->countPending();

        // 5. INSCRIPCIONES (MATCHES)
        // Definimos "Matches" como inscripciones exitosas o en proceso
        $totalMatches = $this->inscripcionService->countByStatus(['CONFIRMADO', 'ACEPTADO', 'EN_CURSO', 'FINALIZADO', 'COMPLETADA']);

        // 6. DESGLOSE INSCRIPCIONES
        $inscripcionesCompletadas = $this->inscripcionService->countByStatus(['FINALIZADO', 'COMPLETADA']);
        $inscripcionesPendientes = $this->inscripcionService->countByStatus('PENDIENTE');
        $inscripcionesAceptadas = $this->inscripcionService->countByStatus(['CONFIRMADO', 'ACEPTADO', 'EN_CURSO']);

        // 7. TOTAL PENDIENTES (General del sistema a revisar por admin)
        // Sumamos voluntarios pendientes + organizaciones pendientes + actividades pendientes
        $totalPendientesGlobal = $voluntariosPendientes + $organizacionesPendientes + $actividadesPendientes;

        $data = [
            'voluntarios' => [
                'total' => $totalVoluntarios,
                'pendientes' => $voluntariosPendientes
            ],
            'organizaciones' => [
                'total' => $totalOrganizaciones,
                'pendientes' => $organizacionesPendientes
            ],
            'actividades' => [
                'total' => $totalActividades,
                'pendientes' => $actividadesPendientes
            ],
            'matches' => [
                'total' => $totalMatches
            ],
            'pendientes_global' => $totalPendientesGlobal,
            'desglose_inscripciones' => [
                'completados' => $inscripcionesCompletadas,
                'pendientes' => $inscripcionesPendientes,
                'aceptados' => $inscripcionesAceptadas
            ]
        ];

        return $this->json($data);
    }
}
