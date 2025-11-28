<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Model\CrearActividadDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/actividades', name: 'api_actividades_')]
class ActividadController extends AbstractController
{
    #[Route('/crear', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        // 1. Buscar la Organización dueña
        $organizacion = $em->getRepository(Organizacion::class)->find($dto->cifOrganizacion);
        
        if (!$organizacion) {
            return $this->json(['error' => 'Organización no encontrada'], 404);
        }

        // 2. Crear Actividad
        $actividad = new Actividad();
        $actividad->setNombre($dto->nombre);
        $actividad->setDescripcion($dto->descripcion);
        $actividad->setOrganizacion($organizacion); // Aquí enlazamos las tablas
        
        if ($dto->fechaInicio) {
            try {
                $actividad->setFechaInicio(new \DateTime($dto->fechaInicio));
            } catch (\Exception $e) {}
        }

        // Guardar arrays como texto
        $actividad->setOds(implode(', ', $dto->ods));

        $em->persist($actividad);
        $em->flush();

        return $this->json(['message' => 'Actividad creada', 'id' => $actividad->getCodActividad()], 201);
    }
    
    // LISTAR TODAS (Para que Angular las pinte)
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $actividades = $em->getRepository(Actividad::class)->findAll();
        // Nota: Al devolver entidades con relaciones, puede dar error circular.
        // Lo ideal es devolver un array simple o usar grupos de serialización.
        return $this->json($actividades);
    }
}