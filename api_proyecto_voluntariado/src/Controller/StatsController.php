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
        $totalVoluntarios = $this->volunteerService->countByStatus(\App\Enum\VolunteerStatus::ACEPTADO);
        $voluntariosPendientes = $this->volunteerService->countByStatus(\App\Enum\VolunteerStatus::PENDIENTE);

        // 3. ORGANIZACIONES (Solo activas - ACEPTADA)
        $totalOrganizaciones = $this->organizationService->countByStatus(\App\Enum\OrganizationStatus::APROBADO);
        $organizacionesPendientes = $this->organizationService->countByStatus(\App\Enum\OrganizationStatus::PENDIENTE);

        // 4. ACTIVIDADES (PROJECTS)
        $totalActividades = $this->activityService->countVisible(); // Assuming this service is already safe or handles strings
        $actividadesPendientes = $this->activityService->countPending();

        // 5. INSCRIPCIONES (MATCHES)
        // Definimos "Matches" como inscripciones exitosas o en proceso
        $totalMatches = $this->inscripcionService->countByStatus([
            \App\Enum\InscriptionStatus::CONFIRMADO, 
            \App\Enum\InscriptionStatus::EN_CURSO, 
            \App\Enum\InscriptionStatus::FINALIZADO, 
            \App\Enum\InscriptionStatus::COMPLETADA
        ]);

        // 6. DESGLOSE INSCRIPCIONES
        $inscripcionesCompletadas = $this->inscripcionService->countByStatus([\App\Enum\InscriptionStatus::FINALIZADO, \App\Enum\InscriptionStatus::COMPLETADA]);
        $inscripcionesPendientes = $this->inscripcionService->countByStatus(\App\Enum\InscriptionStatus::PENDIENTE);
        $inscripcionesAceptadas = $this->inscripcionService->countByStatus([\App\Enum\InscriptionStatus::CONFIRMADO, \App\Enum\InscriptionStatus::EN_CURSO]);

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
