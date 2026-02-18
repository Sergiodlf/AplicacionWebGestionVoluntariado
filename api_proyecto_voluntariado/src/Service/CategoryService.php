<?php

namespace App\Service;

use App\Entity\Habilidad;
use App\Entity\Interes;
use App\Repository\CicloRepository;
use App\Repository\HabilidadRepository;
use App\Repository\InteresRepository;
use App\Repository\NecesidadRepository;
use App\Repository\ODSRepository;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    private CicloRepository $cicloRepository;
    private ODSRepository $odsRepository;
    private HabilidadRepository $habilidadRepository;
    private InteresRepository $interesRepository;
    private NecesidadRepository $necesidadRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CicloRepository $cicloRepository,
        ODSRepository $odsRepository,
        HabilidadRepository $habilidadRepository,
        InteresRepository $interesRepository,
        NecesidadRepository $necesidadRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->cicloRepository = $cicloRepository;
        $this->odsRepository = $odsRepository;
        $this->habilidadRepository = $habilidadRepository;
        $this->interesRepository = $interesRepository;
        $this->necesidadRepository = $necesidadRepository;
        $this->entityManager = $entityManager;
    }

    public function getAllCiclos(): array
    {
        return $this->cicloRepository->findAll();
    }

    public function getAllODS(): array
    {
        return $this->odsRepository->findAll();
    }

    public function getAllHabilidades(): array
    {
        return $this->habilidadRepository->findAll();
    }

    public function getAllIntereses(): array
    {
        return $this->interesRepository->findAll();
    }

    public function getAllNecesidades(): array
    {
        return $this->necesidadRepository->findAll();
    }

    public function createHabilidad(string $nombre): int
    {
        $h = new Habilidad();
        $h->setNombre($nombre);
        $this->entityManager->persist($h);
        $this->entityManager->flush();

        return $h->getId();
    }

    public function deleteHabilidad(int $id): bool
    {
        $h = $this->habilidadRepository->find($id);
        if (!$h) {
            return false;
        }

        // Raw SQL to clean up M2M relation before deleting entity
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM VOLUNTARIOS_HABILIDADES WHERE HABILIDAD_ID = :id', ['id' => $id]);

        $this->entityManager->remove($h);
        $this->entityManager->flush();

        return true;
    }

    public function createInteres(string $nombre): int
    {
        $i = new Interes();
        $i->setNombre($nombre);
        $this->entityManager->persist($i);
        $this->entityManager->flush();

        return $i->getId();
    }

    public function deleteInteres(int $id): bool
    {
        $i = $this->interesRepository->find($id);
        if (!$i) {
            return false;
        }

        // Raw SQL to clean up M2M relation before deleting entity
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM VOLUNTARIOS_INTERESES WHERE INTERES_ID = :id', ['id' => $id]);

        $this->entityManager->remove($i);
        $this->entityManager->flush();

        return true;
    }
}
