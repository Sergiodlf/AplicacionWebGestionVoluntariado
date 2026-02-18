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
     * Finds an activity by ID.
     */
    public function getActivityById(int $id): ?Actividad
    {
        return $this->entityManager->getRepository(Actividad::class)->find($id);
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
        if (isset($data['descripcion'])) {
            $actividad->setDescripcion($data['descripcion']);
        }
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
        $actividad->setMaxParticipantes($data['maxParticipantes']);
        $actividad->setDireccion($data['direccion']);
        if (isset($data['sector'])) {
            $actividad->setSector($data['sector']);
        }

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
     * Retrieves activities based on filters using the Repository.
     */
    public function getActivitiesByFilters(array $filters): array
    {
        return $this->entityManager->getRepository(Actividad::class)->findByFilters($filters);
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
        $actividad = $this->entityManager->getRepository(Actividad::class)->find($id);
        if (!$actividad) {
            throw new \Exception('Actividad no encontrada', 404);
        }
        
        $campoActualizado = 'estado';
        
        // 1. Determine Type and Validate using Enums
        if ($tipo === 'aprobacion') {
            if (!\App\Enum\ActivityApproval::isValid($nuevoEstado)) {
                throw new \InvalidArgumentException("Estado de aprobación inválido: $nuevoEstado");
            }
            $actividad->setEstadoAprobacion(strtoupper($nuevoEstado));
            $campoActualizado = 'estadoAprobacion';

        } elseif ($tipo === 'ejecucion') {
            $enumStatus = \App\Enum\ActivityStatus::fromLegacy($nuevoEstado);
            if (!$enumStatus) {
                throw new \InvalidArgumentException("Estado de ejecución inválido: $nuevoEstado");
            }
            $actividad->setEstado($enumStatus->value);
            $campoActualizado = 'estado';

        } else {
            // Auto-detection logic (Legacy support)
            if (\App\Enum\ActivityApproval::isValid($nuevoEstado)) {
                $actividad->setEstadoAprobacion(strtoupper($nuevoEstado));
                $campoActualizado = 'estadoAprobacion';
            } else {
                $enumStatus = \App\Enum\ActivityStatus::fromLegacy($nuevoEstado);
                 if ($enumStatus) {
                    $actividad->setEstado($enumStatus->value);
                    $campoActualizado = 'estado';
                } else {
                     throw new \InvalidArgumentException("Estado desconocido o inválido: $nuevoEstado");
                }
            }
        }
        
        $this->entityManager->flush();

        return [
            'campo_actualizado' => $campoActualizado,
            'valor_nuevo' => ($campoActualizado === 'estado') ? $actividad->getEstado() : $actividad->getEstadoAprobacion(),
            'actividad' => $actividad
        ];
    }

    /**
     * Checks and updates activity status based on dates.
     */
    public function checkAndUpdateStatus(Actividad $actividad): bool
    {
        $now = new \DateTime();
        $start = $actividad->getFechaInicio();
        $end = $actividad->getFechaFin();
        $estadoActual = $actividad->getEstado();
        $nuevoEstado = null;

        // Reglas de negocio
        if ($start && $now < $start) {
            $nuevoEstado = \App\Enum\ActivityStatus::PENDIENTE->value;
        } elseif ($end && $now > $end) {
            $nuevoEstado = \App\Enum\ActivityStatus::COMPLETADA->value;
        } else {
            // Si ya empezó y no ha terminado (o no tiene fin), está en curso
            $nuevoEstado = \App\Enum\ActivityStatus::EN_CURSO->value;
        }

        // Solo actualizamos si cambia
        if ($nuevoEstado && $estadoActual !== $nuevoEstado) {
            $actividad->setEstado($nuevoEstado);
            return true;
        }

        return false;
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
        $actividad = $this->entityManager->getRepository(Actividad::class)->find($id);
        if (!$actividad) {
            throw new \Exception('Actividad no encontrada', 404);
        }

        // Hard Delete Logic (Modified per user request)
        // 1. Remove all inscriptions explicitly to avoid FK constraints
        foreach ($actividad->getInscripciones() as $inscripcion) {
            $this->entityManager->remove($inscripcion);
        }
        
        // 2. Remove the activity
        $this->entityManager->remove($actividad);
        $this->entityManager->flush();
        
        return 'deleted';
    }

    /**
     * Updates an existing activity.
     */
    public function updateActivity(Actividad $actividad, array $data): Actividad
    {
        if (isset($data['nombre'])) $actividad->setNombre($data['nombre']);
        if (isset($data['descripcion'])) $actividad->setDescripcion($data['descripcion']); // <--- FIXED
        if (isset($data['direccion'])) $actividad->setDireccion($data['direccion']);
        if (isset($data['sector'])) $actividad->setSector($data['sector']);
        if (isset($data['maxParticipantes'])) $actividad->setMaxParticipantes($data['maxParticipantes']);

        // Dates
        if (isset($data['fechaInicio']) && !empty($data['fechaInicio'])) {
            $fInicio = $data['fechaInicio'] instanceof \DateTimeInterface 
                ? $data['fechaInicio'] 
                : new \DateTime($data['fechaInicio']);
            $actividad->setFechaInicio($fInicio);
        }
        if (isset($data['fechaFin']) && !empty($data['fechaFin'])) {
            $fFin = $data['fechaFin'] instanceof \DateTimeInterface 
                ? $data['fechaFin'] 
                : new \DateTime($data['fechaFin']);
            $actividad->setFechaFin($fFin);
        }
        
        // ODS Sync
        if (isset($data['odsIds'])) {
            // Remove old
            foreach ($actividad->getOds() as $od) {
                $actividad->removeOd($od);
            }
            // Add new
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

        // Habilidades Sync
        if (isset($data['habilidadIds'])) {
            foreach ($actividad->getHabilidades() as $h) {
                $actividad->removeHabilidad($h);
            }
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

        $this->entityManager->flush();
        return $actividad;
    }
    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function countVisible(): int
    {
        $now = new \DateTime();
        return $this->entityManager->getRepository(Actividad::class)->createQueryBuilder('a')
            ->select('count(a.codActividad)')
            ->where('a.estadoAprobacion = :aceptada')
            ->andWhere('a.estado != :cancelado')
            ->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
            ->setParameter('aceptada', 'ACEPTADA')
            ->setParameter('cancelado', 'CANCELADO')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPending(): int
    {
        $now = new \DateTime();
        return $this->entityManager->getRepository(Actividad::class)->createQueryBuilder('a')
            ->select('count(a.codActividad)')
            ->where('a.estadoAprobacion = :pendiente')
            ->andWhere('a.estado != :cancelado')
            ->andWhere('a.fechaFin >= :now OR a.fechaFin IS NULL')
            ->setParameter('pendiente', 'PENDIENTE')
            ->setParameter('cancelado', 'CANCELADO')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
