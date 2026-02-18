<?php

namespace App\Controller;

use App\Entity\Administrador;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\VolunteerService;
use App\Service\OrganizationService;

/**
 * S-1: Controller dedicado exclusivamente a la gestión de perfil del usuario autenticado.
 */
#[Route('/api/auth', name: 'api_auth_')]
class ProfileController extends AbstractController
{
    use ApiErrorTrait;

    public function __construct(
        private VolunteerService $volunteerService,
        private OrganizationService $organizationService
    ) {}

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function getProfile(SerializerInterface $serializer): JsonResponse
    {
        try {
            $securityUser = $this->getUser();
            $user = $securityUser?->getDomainUser();

            if (!$user) {
                return $this->errorResponse('Usuario no autenticado o token inválido', 401);
            }

            // S-4: Use Serializer Groups for lean responses
            $groups = ['user:read'];
            $tipo = 'desconocido';

            if ($user instanceof Administrador) {
                $tipo = 'admin';
            } elseif ($user instanceof Voluntario) {
                $tipo = 'voluntario';
                $groups[] = 'voluntario:read';
            } elseif ($user instanceof Organizacion) {
                $tipo = 'organizacion';
                $groups[] = 'org:read';
            }

            return $this->json([
                'tipo' => $tipo,
                'datos' => $user
            ], 200, [], ['groups' => $groups]);

        } catch (\Throwable $e) {
            return $this->errorResponse('Error interno: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', 401);
        }

        $data = json_decode($request->getContent(), true);

        try {
            if ($user instanceof Voluntario) {
                $userManaged = $this->volunteerService->getById($user->getDni());
                if (!$userManaged) return $this->errorResponse('Usuario no encontrado', 404);
                
                $this->volunteerService->updateProfile($userManaged, $data);
            } 
            elseif ($user instanceof Organizacion) {
                $orgManaged = $this->organizationService->getByCif($user->getCif());
                if (!$orgManaged) return $this->errorResponse('Organización no encontrada', 404);
                
                $this->organizationService->updateProfile($orgManaged, $data);
            } 
            else {
                return $this->errorResponse('Tipo de usuario no soportado para actualización de perfil', 400);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar perfil', 500, $e->getMessage());
        }

        return $this->json(['message' => 'Perfil actualizado correctamente']);
    }
}
