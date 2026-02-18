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

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $type = $request->query->get('type');
        
        $data = [];
        $groups = [];

        switch ($type) {
            case 'ciclos':
                $data = $this->categoryService->getAllCiclos();
                $groups = ['ciclo:read'];
                break;
            case 'ods':
                $data = $this->categoryService->getAllODS();
                $groups = ['ods:read'];
                break;
            case 'habilidades':
                $data = $this->categoryService->getAllHabilidades();
                $groups = ['habilidad:read'];
                break;
            case 'intereses':
                $data = $this->categoryService->getAllIntereses();
                $groups = ['interes:read'];
                break;
            case 'necesidades':
                $data = $this->categoryService->getAllNecesidades();
                $groups = ['necesidad:read'];
                break;
            default:
                return new JsonResponse(['error' => 'Tipo de categoría no válido o no especificado (ciclos, ods, habilidades, intereses, necesidades)'], 400);
        }

        return new JsonResponse(
            $this->serializer->serialize($data, 'json', ['groups' => $groups]),
            200,
            [],
            true
        );
    }

    // Deprecated specific endpoints - keeping for compatibility if needed, or removing?
    // Plan said "Integrate", implied replacing usage.
    // For now I will remove the specific GET endpoints to enforce the new structure as discussed.

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
