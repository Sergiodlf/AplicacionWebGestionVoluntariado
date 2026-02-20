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
            $userWrapper = $this->getUser();

            if (!$userWrapper instanceof \App\Security\User\User) {
                return $this->errorResponse('Usuario no autenticado o token inválido', 401);
            }

            $user = $userWrapper->getDomainUser();
            if (!$user) {
                return $this->errorResponse('Usuario de dominio no encontrado', 404);
            }

            // S-4: Use Standard User methods
            $tipo = $userWrapper->getType();
            $groups = $userWrapper->getSerializationGroups();

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
        $userWrapper = $this->getUser();
        
        if (!$userWrapper instanceof \App\Security\User\User) {
            return $this->errorResponse('Usuario no autenticado o token inválido', 401);
        }

        $user = $userWrapper->getDomainUser();

        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', 401);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $type = $userWrapper->getType();

            if ($type === 'voluntario') {
                $userManaged = $this->volunteerService->getById($user->getDni());
                if (!$userManaged) return $this->errorResponse('Usuario no encontrado', 404);
                
                $this->volunteerService->updateProfile($userManaged, $data);
            } 
            elseif ($type === 'organizacion') {
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

        // Return updated profile data for immediate frontend sync
        $groups = $userWrapper->getSerializationGroups();
        return $this->json([
            'message' => 'Perfil actualizado correctamente',
            'tipo' => $type,
            'datos' => $userManaged ?: $orgManaged
        ], 200, [], ['groups' => $groups]);
    }
}
