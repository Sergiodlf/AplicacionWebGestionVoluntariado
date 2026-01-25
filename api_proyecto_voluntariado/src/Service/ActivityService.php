<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{
    private $entityManager;
    private $odsRepository;
    private $habilidadRepository;
    private $necesidadRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        \App\Repository\ODSRepository $odsRepository,
        \App\Repository\HabilidadRepository $habilidadRepository,
        \App\Repository\NecesidadRepository $necesidadRepository
    ) {
        $this->entityManager = $entityManager;
        $this->odsRepository = $odsRepository;
        $this->habilidadRepository = $habilidadRepository;
        $this->necesidadRepository = $necesidadRepository;
    }

    /**
     * Checks if an activity with the same name already exists for an organization.
     */
    public function activityExists(string $nombre, Organizacion $organizacion): bool
    {
        $repo = $this->entityManager->getRepository(Actividad::class);
        $duplicada = $repo->findOneBy([
            'nombre' => $nombre,
            'organizacion' => $organizacion
        ]);
        
        return $duplicada !== null;
    }

    /**
     * Creates a new activity using the ORM.
     */
    public function createActivity(array $data, Organizacion $organizacion): ?Actividad
    {
        $actividad = new Actividad();
        $actividad->setNombre($data['nombre']);
        $now = new \DateTime();
        $fechaInicio = isset($data['fechaInicio']) ? new \DateTime($data['fechaInicio']) : new \DateTime();
        
        // Determine initial state based on start date
        // If start date is in the future (> today), it is PENDING
        // Otherwise it is OPEN (ABIERTA)
        $estadoCalculado = ($fechaInicio > $now) ? 'PENDIENTE' : 'ABIERTA';
        
        $actividad->setEstado($data['estado'] ?? $estadoCalculado);
        $actividad->setEstadoAprobacion($data['estadoAprobacion'] ?? 'PENDIENTE');
        $actividad->setOrganizacion($organizacion);
        
        $actividad->setFechaInicio($fechaInicio);
        
        if (isset($data['fechaFin'])) {
            $actividad->setFechaFin(new \DateTime($data['fechaFin']));
        }
        
        $actividad->setMaxParticipantes($data['maxParticipantes']);
        $actividad->setDireccion($data['direccion']);

        // Link ODS
        if (!empty($data['odsIds'])) { // Nota: en el DTO se llama 'ods', mapeado a este campo
            foreach ($data['odsIds'] as $item) {
                $ods = null;
                if (is_numeric($item)) {
                    $ods = $this->odsRepository->find($item);
                } elseif (is_string($item)) {
                    $ods = $this->odsRepository->findOneBy(['nombre' => $item]);
                }
                
                if ($ods) {
                    $actividad->addOd($ods);
                }
            }
        }

        // Link Habilidades
        if (!empty($data['habilidadIds'])) {
            foreach ($data['habilidadIds'] as $item) {
                $h = null;
                if (is_numeric($item)) {
                    $h = $this->habilidadRepository->find($item);
                } elseif (is_string($item)) {
                    $h = $this->habilidadRepository->findOneBy(['nombre' => $item]);
                }

                if ($h) {
                    $actividad->addHabilidad($h);
                }
            }
        }

        // Link Necesidades
        /*if (!empty($data['necesidadIds'])) {
            foreach ($data['necesidadIds'] as $item) {
                 $n = null;
                if (is_numeric($item)) {
                    $n = $this->necesidadRepository->find($item);
                } elseif (is_string($item)) {
                    $n = $this->necesidadRepository->findOneBy(['nombre' => $item]);
                }

                if ($n) {
                    $actividad->addNecesidad($n);
                }
            }
        }*/

        $this->entityManager->persist($actividad);
        $this->entityManager->flush();

        return $actividad;
    }

    /**
     * Updates the status of an activity.
     * Handles both 'estado' (execution) and 'estadoAprobacion' (approval).
     * 
     * @param int $id The activity ID
     * @param string $nuevoEstado The new status string
     * @param string|null $tipo Optional type ('aprobacion' or 'ejecucion') to disambiguate
     * @return array Result metadata including which field was updated
     * @throws \Exception If activity not found or status invalid
     */
    public function updateActivityStatus(int $id, string $nuevoEstado, ?string $tipo = null): array
    {
        file_put_contents('debug_activity.txt', "\n--- UPDATE ACTIVITY STATUS ---\nID: $id, NewState: $nuevoEstado, Type: " . ($tipo ?? 'NULL') . "\n", FILE_APPEND);
        
        $actividad = $this->entityManager->getRepository(Actividad::class)->find($id);
        if (!$actividad) {
            file_put_contents('debug_activity.txt', "ERROR: Activity not found.\n", FILE_APPEND);
            throw new \Exception('Actividad no encontrada', 404);
        }

        $nuevoEstadoUpper = strtoupper($nuevoEstado);
        $estadosAprobacion = ['ACEPTADA', 'RECHAZADA', 'PENDIENTE'];
        $estadosEjecucion = ['PENDIENTE', 'EN CURSO', 'FINALIZADO', 'CANCELADO', 'ABIERTA'];
        
        $campoActualizado = 'estado'; // Default fallback

        if ($tipo === 'aprobacion') {
            file_put_contents('debug_activity.txt', "Type is 'aprobacion'. Checking whitelist...\n", FILE_APPEND);
            if (!in_array($nuevoEstadoUpper, $estadosAprobacion)) {
                file_put_contents('debug_activity.txt', "ERROR: Invalid approval status: $nuevoEstadoUpper\n", FILE_APPEND);
                throw new \InvalidArgumentException("Estado de aprobación inválido: $nuevoEstadoUpper");
            }
            $actividad->setEstadoAprobacion($nuevoEstadoUpper);
            $campoActualizado = 'estadoAprobacion';

        } elseif ($tipo === 'ejecucion') {
            file_put_contents('debug_activity.txt', "Type is 'ejecucion'. Checking whitelist...\n", FILE_APPEND);
            if (!in_array($nuevoEstadoUpper, $estadosEjecucion)) {
                file_put_contents('debug_activity.txt', "ERROR: Invalid execution status: $nuevoEstadoUpper\n", FILE_APPEND);
                throw new \InvalidArgumentException("Estado de ejecución inválido: $nuevoEstadoUpper");
            }
            $actividad->setEstado($nuevoEstadoUpper);
            $campoActualizado = 'estado';

        } else {
            // Auto-detection logic
            file_put_contents('debug_activity.txt', "No type provided. Auto-detecting...\n", FILE_APPEND);
            if (in_array($nuevoEstadoUpper, ['ACEPTADA', 'RECHAZADA'])) {
                $actividad->setEstadoAprobacion($nuevoEstadoUpper);
                $campoActualizado = 'estadoAprobacion';
                
            } elseif (in_array($nuevoEstadoUpper, ['EN CURSO', 'FINALIZADO', 'CANCELADO', 'ABIERTA'])) {
                $actividad->setEstado($nuevoEstadoUpper);
                $campoActualizado = 'estado';

            } elseif ($nuevoEstadoUpper === 'PENDIENTE') {
                // 'PENDIENTE' is ambiguous, default to execution state for backward compatibility
                $actividad->setEstado('PENDIENTE');
                $campoActualizado = 'estado';
            } else {
                 file_put_contents('debug_activity.txt', "ERROR: Unknown status: $nuevoEstadoUpper\n", FILE_APPEND);
                 throw new \InvalidArgumentException("Estado desconocido o inválido: $nuevoEstadoUpper");
            }
        }
        
        file_put_contents('debug_activity.txt', "Field updated: $campoActualizado. Flushing to DB...\n", FILE_APPEND);

        $this->entityManager->flush();

        return [
            'campo_actualizado' => $campoActualizado,
            'valor_nuevo' => ($campoActualizado === 'estado') ? $actividad->getEstado() : $actividad->getEstadoAprobacion(),
            'actividad' => $actividad
        ];
    }

    /**
     * Deletes or Cancels an activity based on existing inscriptions.
     * 
     * @param int $id The activity ID
     * @return string 'deleted' or 'cancelled'
     * @throws \Exception If activity not found
     */
    public function deleteActivity(int $id): string
    {
        file_put_contents('debug_activity.txt', "\n--- DELETE ACTIVITY ---\nID: $id\n", FILE_APPEND);
        
        $actividad = $this->entityManager->getRepository(Actividad::class)->find($id);
        if (!$actividad) {
            file_put_contents('debug_activity.txt', "ERROR: Activity not found.\n", FILE_APPEND);
            throw new \Exception('Actividad no encontrada', 404);
        }

        // Check if there are any inscriptions
        $inscripcionesCount = $actividad->getInscripciones()->count();
        file_put_contents('debug_activity.txt', "Inscriptions found: $inscripcionesCount\n", FILE_APPEND);

        if ($inscripcionesCount > 0) {
            // Soft Delete: Mark as CANCELADO
            file_put_contents('debug_activity.txt', "Action: Soft Delete (CANCELADO)\n", FILE_APPEND);
            $actividad->setEstado('CANCELADO');
            $this->entityManager->flush();
            return 'cancelled';
        } else {
            // Hard Delete: Remove entity
            file_put_contents('debug_activity.txt', "Action: Hard Delete (REMOVE)\n", FILE_APPEND);
            $this->entityManager->remove($actividad);
            $this->entityManager->flush();
            return 'deleted';
        }
    }
}
