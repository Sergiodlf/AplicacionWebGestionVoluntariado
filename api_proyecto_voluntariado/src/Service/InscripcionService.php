<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;

class InscripcionService
{
    private $entityManager;
    private $notificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        \App\Service\NotificationService $notificationService
    ) {
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function getAll(?string $estado = null): array
    {
        $repo = $this->entityManager->getRepository(Inscripcion::class);

        if ($estado) {
            $estados = array_map('strtoupper', array_map('trim', explode(',', $estado)));
            // Map 'ACEPTADO' to 'CONFIRMADO' just in case
            foreach ($estados as $k => $v) {
                if ($v === 'ACEPTADO') $estados[$k] = 'CONFIRMADO';
            }
            return $repo->findBy(['estado' => $estados]);
        }
        
        return $repo->findAll();
    }

    public function getById(int $id): ?Inscripcion
    {
        return $this->entityManager->getRepository(Inscripcion::class)->find($id);
    }

    /**
     * Counts active inscriptions for an activity.
     */
    public function countActiveInscriptions(Actividad $actividad): int
    {
        return $this->entityManager->getRepository(Inscripcion::class)->countActiveByActivity($actividad);
    }

    /**
     * Checks if a volunteer is already inscribed in an activity.
     */
    public function isVolunteerInscribed(Actividad $actividad, Voluntario $voluntario): ?Inscripcion
    {
        return $this->entityManager->getRepository(Inscripcion::class)->findOneBy([
            'voluntario' => $voluntario,
            'actividad' => $actividad
        ]);
    }

    /**
     * Creates a new inscription or reactivates an existing one.
     */
    public function createInscription(Actividad $actividad, Voluntario $voluntario, ?Inscripcion $existing = null, bool $autoAccept = false): Inscripcion
    {
        if ($existing) {
             $inscripcion = $existing;
             // Reactivate
        } else {
            $inscripcion = new Inscripcion();
            $inscripcion->setActividad($actividad);
            $inscripcion->setVoluntario($voluntario);
            $this->entityManager->persist($inscripcion);
        }

        $inscripcion->setEstado($autoAccept ? 'CONFIRMADO' : 'PENDIENTE'); 
        $this->entityManager->flush();

        return $inscripcion;
    }

    public function updateStatus(Inscripcion $inscripcion, string $nuevoEstado): Inscripcion
    {
        $inscripcion->setEstado(strtoupper($nuevoEstado));
        $this->entityManager->flush();

        // Send Notifications
        $this->sendNotification($inscripcion, $nuevoEstado);

        return $inscripcion;
    }

    public function delete(Inscripcion $inscripcion): void
    {
        $this->entityManager->remove($inscripcion);
        $this->entityManager->flush();
    }

    public function getByVoluntario(Voluntario $voluntario, ?string $estado = null): array
    {
        $criteria = ['voluntario' => $voluntario];
        if ($estado) {
            $normalized = strtoupper($estado);
            if ($normalized === 'ACEPTADO') {
                $normalized = 'CONFIRMADO';
            }
            $criteria['estado'] = $normalized;
        }

        return $this->entityManager->getRepository(Inscripcion::class)->findBy($criteria, ['id' => 'DESC']);
    }

    public function getByOrganizacion(string $cif, ?string $estado = null): array
    {
        return $this->entityManager->getRepository(Inscripcion::class)->findByOrganizacionAndEstado($cif, $estado);
    }

    private function sendNotification(Inscripcion $inscripcion, string $nuevoEstado): void
    {
        try {
            $voluntario = $inscripcion->getVoluntario();
            $actividad = $inscripcion->getActividad();
            $actividadNombre = $actividad ? $actividad->getNombre() : 'Actividad';
            $estadoUpper = strtoupper(trim($nuevoEstado));
            $titulo = null;
            $cuerpo = null;

            if ($estadoUpper === 'CONFIRMADO' || $estadoUpper === 'ACEPTADO') {
                $titulo = "¡Solicitud Aceptada!";
                $cuerpo = "Un administrador ha aceptado tu solicitud de voluntariado. Toca para ver más detalles.";
                
                if ($voluntario) {
                    $this->notificationService->sendToUser(
                        $voluntario, 
                        $titulo, 
                        $cuerpo, 
                        [
                            'title' => $titulo,
                            'body' => $cuerpo,
                            'type' => 'MATCH_ACCEPTED',
                            'matchId' => (string)$inscripcion->getId(),
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                        ]
                    );
                }
                $titulo = null; // Prevent generic sending
            } elseif ($estadoUpper === 'RECHAZADO') {
                $titulo = "Solicitud Actualizada";
                $cuerpo = "El estado de tu solicitud para " . $actividadNombre . " ha cambiado a " . $estadoUpper;
            }

            if ($titulo && $voluntario) {
                $this->notificationService->sendToUser(
                    $voluntario, 
                    $titulo, 
                    $cuerpo, 
                    ['click_action' => 'FLUTTER_NOTIFICATION_CLICK', 'id_inscripcion' => (string)$inscripcion->getId()]
                );
            }
        } catch (\Exception $e) {
            // Ignore notification errors
        }
    }
    public function countByStatus($status): int
    {
        $repo = $this->entityManager->getRepository(Inscripcion::class);
        if (is_array($status)) {
            return $repo->count(['estado' => $status]);
        }
        return $repo->count(['estado' => $status]);
    }
}
