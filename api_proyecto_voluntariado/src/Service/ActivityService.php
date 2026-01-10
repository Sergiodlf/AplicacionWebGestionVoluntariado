<?php

namespace App\Service;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
     * Creates a new activity using Raw SQL (as per project requirements).
     */
    public function createActivity(array $data, Organizacion $organizacion): bool
    {
        $conn = $this->entityManager->getConnection();
        
        $sql = "
            INSERT INTO ACTIVIDADES (
                NOMBRE, ESTADO, ESTADO_APROBACION, CIF_EMPRESA, 
                FECHA_INICIO, FECHA_FIN, MAX_PARTICIPANTES, 
                DIRECCION, ODS, HABILIDADES
            ) VALUES (
                :nombre, :estado, :estadoAprobacion, :cif, 
                :fInicio, :fFin, :maxP, 
                :direccion, :ods, :habilidades
            )
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeStatement([
            'nombre'           => $data['nombre'],
            'estado'           => $data['estado'] ?? 'En Curso',
            'estadoAprobacion' => $data['estadoAprobacion'] ?? 'PENDIENTE',
            'cif'              => $organizacion->getCif(),
            'fInicio'          => $data['fechaInicioSql'],
            'fFin'             => $data['fechaFinSql'],
            'maxP'             => $data['maxParticipantes'],
            'direccion'        => $data['direccion'],
            'ods'              => $data['odsJson'],
            'habilidades'      => $data['habilidadesJson']
        ]);

        return $result > 0;
    }
}
