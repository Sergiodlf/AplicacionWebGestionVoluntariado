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

        // 2. VOLUNTARIOS (Solo activos - ACEPTADO)
        $totalVoluntarios = $voluntarioRepo->count(['estadoVoluntario' => 'ACEPTADO']);
        $voluntariosPendientes = $voluntarioRepo->count(['estadoVoluntario' => 'PENDIENTE']);

        // 3. ORGANIZACIONES (Solo activas - ACEPTADA)
        $totalOrganizaciones = $organizacionRepo->count(['estado' => 'ACEPTADA']);
        $organizacionesPendientes = $organizacionRepo->count(['estado' => 'PENDIENTE']);

        // 4. ACTIVIDADES (PROJECTS)
        $actividadRepo = $em->getRepository(Actividad::class);
        $now = new \DateTime();

        // Total Actividades Visibles (Aceptadas, No Canceladas, No Pasadas)
        $totalActividades = $actividadRepo->createQueryBuilder('a')
            ->select('count(a.codActividad)')
            ->where('a.estadoAprobacion = :aceptada')
            ->andWhere('a.estado != :cancelado')
            ->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
            ->setParameter('aceptada', 'ACEPTADA')
            ->setParameter('cancelado', 'CANCELADO')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // Actividades Pendientes de moderación (No Canceladas, No Pasadas)
        $actividadesPendientes = $actividadRepo->createQueryBuilder('a')
            ->select('count(a.codActividad)')
            ->where('a.estadoAprobacion = :pendiente')
            ->andWhere('a.estado != :cancelado')
            ->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
            ->setParameter('pendiente', 'PENDIENTE')
            ->setParameter('cancelado', 'CANCELADO')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

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
