<?php

namespace App\Controller;

use App\Repository\CicloRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ciclos')]
class CicloController extends AbstractController
{
    #[Route('', name: 'api_ciclos_list', methods: ['GET'])]
    public function getAllCiclos(CicloRepository $cicloRepository): JsonResponse
    {
        $ciclos = $cicloRepository->findAll();
        
        $data = [];
        foreach ($ciclos as $ciclo) {
            $data[] = [
                'nombre' => $ciclo->getNombre(),
                'curso' => $ciclo->getCurso(),
            ];
        }

        return $this->json($data);
    }
}
