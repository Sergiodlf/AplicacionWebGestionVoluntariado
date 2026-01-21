<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stats', name: 'api_stats_')]
class StatsController extends AbstractController
{
    #[Route('/general', name: 'general', methods: ['GET'])]
    public function getGeneralStats(EntityManagerInterface $em): JsonResponse
    {
        // 1. REPOSITORIOS
        $voluntarioRepo = $em->getRepository(Voluntario::class);
        $organizacionRepo = $em->getRepository(Organizacion::class);
        $inscripcionRepo = $em->getRepository(Inscripcion::class);

        // 2. VOLUNTARIOS
        $totalVoluntarios = $voluntarioRepo->count([]);
        $voluntariosPendientes = $voluntarioRepo->count(['estadoVoluntario' => 'PENDIENTE']);

        // 3. ORGANIZACIONES
        $totalOrganizaciones = $organizacionRepo->count([]);
        $organizacionesPendientes = $organizacionRepo->count(['estado' => 'pendiente']);

        // 4. ACTIVIDADES (PROJECTS)
        $actividadRepo = $em->getRepository(Actividad::class);
        $totalActividades = $actividadRepo->count([]);
        // Asumiendo que 'estadoAprobacion' = 'PENDIENTE' significa pendiente de moderación
        $actividadesPendientes = $actividadRepo->count(['estadoAprobacion' => 'PENDIENTE']);

        // 5. INSCRIPCIONES (MATCHES)
        // Definimos "Matches" como inscripciones exitosas o en proceso
        $totalMatches = $inscripcionRepo->count([
            'estado' => ['CONFIRMADO', 'ACEPTADO', 'EN_CURSO', 'FINALIZADO', 'COMPLETADA']
        ]);

        // 6. DESGLOSE INSCRIPCIONES
        $inscripcionesCompletadas = $inscripcionRepo->count(['estado' => ['FINALIZADO', 'COMPLETADA']]);
        $inscripcionesPendientes = $inscripcionRepo->count(['estado' => 'PENDIENTE']);
        $inscripcionesAceptadas = $inscripcionRepo->count(['estado' => ['CONFIRMADO', 'ACEPTADO', 'EN_CURSO']]);

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
