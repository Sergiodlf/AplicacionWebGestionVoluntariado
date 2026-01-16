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
        if (!empty($data['odsIds'])) {
            foreach ($data['odsIds'] as $id) {
                $ods = $this->odsRepository->find($id);
                if ($ods) {
                    $actividad->addOd($ods);
                }
            }
        }

        // Link Habilidades
        if (!empty($data['habilidadIds'])) {
            foreach ($data['habilidadIds'] as $id) {
                $h = $this->habilidadRepository->find($id);
                if ($h) {
                    $actividad->addHabilidad($h);
                }
            }
        }

        // Link Necesidades
        if (!empty($data['necesidadIds'])) {
            foreach ($data['necesidadIds'] as $id) {
                $n = $this->necesidadRepository->find($id);
                if ($n) {
                    $actividad->addNecesidad($n);
                }
            }
        }

        $this->entityManager->persist($actividad);
        $this->entityManager->flush();

        return $actividad;
    }
}
