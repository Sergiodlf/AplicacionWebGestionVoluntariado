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
     * Updates an existing activity.
     */
    public function updateActivity(Actividad $actividad, array $data): Actividad
    {
        if (isset($data['nombre'])) $actividad->setNombre($data['nombre']);
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
}
