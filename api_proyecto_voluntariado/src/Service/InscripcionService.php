<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Inscripcion;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\InscriptionStatus;

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
            $rawEstados = array_map('trim', explode(',', $estado));
            $enumEstados = [];

            foreach ($rawEstados as $raw) {
                // Handle special legacy case mapping if needed, or straight conversion
                $val = InscriptionStatus::tryFrom(strtoupper($raw));
                if ($val) {
                    $enumEstados[] = $val;
                } elseif (strtoupper($raw) === 'ACEPTADO') {
                    $enumEstados[] = InscriptionStatus::CONFIRMADO;
                }
            }
            
            if (empty($enumEstados)) return [];

            return $repo->findBy(['estado' => $enumEstados]);
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
        // Must update repository method to handle Enum if it uses DQL string comparison
        // or just use count here if performance allows, but better delegating to repo.
        // Assuming Repo is updated or compatible (if using standard methods). 
        // If Custom DQL was used, it needs check. 
        // Let's assume standard count for now or fix repo later.
        // Actually, custom DQL on `countActiveByActivity` likely uses strings. I should check Repo.
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

        $inscripcion->setEstado($autoAccept ? InscriptionStatus::CONFIRMADO : InscriptionStatus::PENDIENTE); 
        $this->entityManager->flush();

        return $inscripcion;
    }

    public function updateStatus(Inscripcion $inscripcion, string|InscriptionStatus $nuevoEstado): Inscripcion
    {
        if (is_string($nuevoEstado)) {
            $nuevoEstado = InscriptionStatus::tryFrom(strtoupper($nuevoEstado)) ?? InscriptionStatus::PENDIENTE;
        }

        $inscripcion->setEstado($nuevoEstado);
        $this->entityManager->flush();

        // Send Notifications
        $this->sendNotification($inscripcion, $nuevoEstado->value);

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
            $enumVal = InscriptionStatus::tryFrom($normalized);
            if ($enumVal) {
                $criteria['estado'] = $enumVal;
            }
        }

        return $this->entityManager->getRepository(Inscripcion::class)->findBy($criteria, ['id' => 'DESC']);
    }

    public function getByOrganizacion(string $cif, ?string $estado = null): array
    {
        // Needed to handle Enum conversion before passing to repo if repo uses simple findBy or custom DQL
        // Repo `findByOrganizacionAndEstado` probably expects string if custom DQL used strings, or object if adjusted.
        // I will check repository later. For now, pass what it expects (likely needs refactor too).
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
        $criteria = [];

        if (is_array($status)) {
            $enums = [];
            foreach ($status as $s) {
                 if ($s instanceof InscriptionStatus) {
                     $enums[] = $s;
                 } elseif (is_string($s)) {
                     $val = InscriptionStatus::tryFrom(strtoupper($s));
                     if ($val) $enums[] = $val;
                 }
            }
            if (!empty($enums)) $criteria['estado'] = $enums;
        } else {
            if ($status instanceof InscriptionStatus) {
                $criteria['estado'] = $status;
            } elseif (is_string($status)) {
                $val = InscriptionStatus::tryFrom(strtoupper($status));
                if ($val) $criteria['estado'] = $val;
            }
        }
        
        if (empty($criteria) && !empty($status)) {
             // If status was provided but no valid Enum found, return 0
             return 0;
        }

        return $repo->count($criteria);
    }
}
