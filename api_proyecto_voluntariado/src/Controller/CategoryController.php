<?php

namespace App\Controller;

use App\Repository\ODSRepository;
use App\Repository\HabilidadRepository;
use App\Repository\InteresRepository;
use App\Repository\NecesidadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Habilidad;
use App\Entity\Interes;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    #[Route('/ods', name: 'api_categories_ods', methods: ['GET'])]
    public function getODS(ODSRepository $repository): JsonResponse
    {
        $data = $repository->findAll();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'ods:read']),
            200,
            [],
            true
        );
    }

    #[Route('/habilidades', name: 'api_categories_habilidades', methods: ['GET'])]
    public function getHabilidades(HabilidadRepository $repository): JsonResponse
    {
        $data = $repository->findAll();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'habilidad:read']),
            200,
            [],
            true
        );
    }

    #[Route('/intereses', name: 'api_categories_intereses', methods: ['GET'])]
    public function getIntereses(InteresRepository $repository): JsonResponse
    {
        $data = $repository->findAll();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'interes:read']),
            200,
            [],
            true
        );
    }

    #[Route('/necesidades', name: 'api_categories_necesidades', methods: ['GET'])]
    public function getNecesidades(NecesidadRepository $repository): JsonResponse
    {
        $data = $repository->findAll();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'necesidad:read']),
            200,
            [],
            true
        );
    }

    #[Route('/habilidades', name: 'api_categories_habilidades_create', methods: ['POST'])]
    public function createHabilidad(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['nombre']) || trim($data['nombre']) === '') {
            return new JsonResponse(['error' => 'Nombre es requerido y no puede estar vacío'], 400);
        }

        $h = new Habilidad();
        $h->setNombre($data['nombre']);
        $this->entityManager->persist($h);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Habilidad creada', 'id' => $h->getId()], 201);
    }

    #[Route('/habilidades/{id}', name: 'api_categories_habilidades_delete', methods: ['DELETE'])]
    public function deleteHabilidad(int $id, HabilidadRepository $repository): JsonResponse
    {
        $h = $repository->find($id);
        if (!$h) return new JsonResponse(['error' => 'No encontrada'], 404);

        // Manually remove relationship from Voluntarios to avoid FK constraint violation
        // Since Voluntario is the owning side, we must do it from there or execute raw SQL.
        // Doing it via DQL/ORM is cleaner but heavier if many volunteers.
        // Given the expected scale, a raw SQL query to the join table is most efficient and safest for a quick fix.
        
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM VOLUNTARIOS_HABILIDADES WHERE HABILIDAD_ID = :id', ['id' => $id]);

        $this->entityManager->remove($h);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Habilidad eliminada'], 200);
    }

    #[Route('/intereses', name: 'api_categories_intereses_create', methods: ['POST'])]
    public function createInteres(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['nombre']) || trim($data['nombre']) === '') {
            return new JsonResponse(['error' => 'Nombre es requerido y no puede estar vacío'], 400);
        }

        $i = new Interes();
        $i->setNombre($data['nombre']);
        $this->entityManager->persist($i);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Interes creado', 'id' => $i->getId()], 201);
    }

    #[Route('/intereses/{id}', name: 'api_categories_intereses_delete', methods: ['DELETE'])]
    public function deleteInteres(int $id, InteresRepository $repository): JsonResponse
    {
        $i = $repository->find($id);
        if (!$i) return new JsonResponse(['error' => 'No encontrada'], 404);

        // Same for Intereses
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM VOLUNTARIOS_INTERESES WHERE INTERES_ID = :id', ['id' => $id]);

        $this->entityManager->remove($i);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Interes eliminado'], 200);
    }
}
