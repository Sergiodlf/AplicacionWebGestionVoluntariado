<?php

namespace App\Controller;

use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    private SerializerInterface $serializer;
    private CategoryService $categoryService;

    public function __construct(SerializerInterface $serializer, CategoryService $categoryService)
    {
        $this->serializer = $serializer;
        $this->categoryService = $categoryService;
    }

    #[Route('/ciclos', name: 'api_categories_ciclos', methods: ['GET'])]
    public function getCiclos(): JsonResponse
    {
        $ciclos = $this->categoryService->getAllCiclos();
        
        $data = [];
        foreach ($ciclos as $ciclo) {
            $data[] = [
                'nombre' => $ciclo->getNombre(),
                'curso' => $ciclo->getCurso(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/ods', name: 'api_categories_ods', methods: ['GET'])]
    public function getODS(): JsonResponse
    {
        $data = $this->categoryService->getAllODS();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'ods:read']),
            200,
            [],
            true
        );
    }

    #[Route('/habilidades', name: 'api_categories_habilidades', methods: ['GET'])]
    public function getHabilidades(): JsonResponse
    {
        $data = $this->categoryService->getAllHabilidades();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'habilidad:read']),
            200,
            [],
            true
        );
    }

    #[Route('/intereses', name: 'api_categories_intereses', methods: ['GET'])]
    public function getIntereses(): JsonResponse
    {
        $data = $this->categoryService->getAllIntereses();
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => 'interes:read']),
            200,
            [],
            true
        );
    }

    #[Route('/necesidades', name: 'api_categories_necesidades', methods: ['GET'])]
    public function getNecesidades(): JsonResponse
    {
        $data = $this->categoryService->getAllNecesidades();
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

        $id = $this->categoryService->createHabilidad($data['nombre']);

        return new JsonResponse(['status' => 'Habilidad creada', 'id' => $id], 201);
    }

    #[Route('/habilidades/{id}', name: 'api_categories_habilidades_delete', methods: ['DELETE'])]
    public function deleteHabilidad(int $id): JsonResponse
    {
        $success = $this->categoryService->deleteHabilidad($id);
        if (!$success) {
            return new JsonResponse(['error' => 'No encontrada'], 404);
        }

        return new JsonResponse(['status' => 'Habilidad eliminada'], 200);
    }

    #[Route('/intereses', name: 'api_categories_intereses_create', methods: ['POST'])]
    public function createInteres(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['nombre']) || trim($data['nombre']) === '') {
            return new JsonResponse(['error' => 'Nombre es requerido y no puede estar vacío'], 400);
        }

        $id = $this->categoryService->createInteres($data['nombre']);

        return new JsonResponse(['status' => 'Interes creado', 'id' => $id], 201);
    }

    #[Route('/intereses/{id}', name: 'api_categories_intereses_delete', methods: ['DELETE'])]
    public function deleteInteres(int $id): JsonResponse
    {
        $success = $this->categoryService->deleteInteres($id);
        if (!$success) {
            return new JsonResponse(['error' => 'No encontrada'], 404);
        }

        return new JsonResponse(['status' => 'Interes eliminado'], 200);
    }
}
