<?php

namespace App\Controller;


use App\Service\OrganizationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/organizations')]
class OrganizacionController extends AbstractController
{
    use ApiErrorTrait;

    private OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    //#[Route('/api/organizations', name: 'api_organizations_list', methods: ['GET'])]
    #[Route('', name: 'api_organizations_list', methods: ['GET'])]
    public function getOrganizations(Request $request): JsonResponse
    {
        $criteria = [];
        if ($estado = $request->query->get('estado')) {
            $criteria['estado'] = $estado;
        }

        $organizaciones = $this->organizationService->getAll($criteria);

        return $this->json(
            $organizaciones,
            Response::HTTP_OK,
            [],
            ['groups' => ['org:read']]
        );
    }

    //#[Route('/api/organizations', name: 'api_organizations_create', methods: ['POST'])]
    #[Route('', name: 'api_organizations_create', methods: ['POST'])]
    public function createOrganization(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var \App\Model\RegistroOrganizacionDTO $dto */
            $dto = $serializer->deserialize(
                $request->getContent(),
                \App\Model\RegistroOrganizacionDTO::class,
                'json'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Formato JSON inválido.', Response::HTTP_BAD_REQUEST);
        }

        // 1. VALIDACIÓN
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->errorResponse('Errores de validación', Response::HTTP_BAD_REQUEST, $errorMessages);
        }

        // Uniqueness check
        $dupeError = $this->organizationService->checkDuplicates($dto->cif, $dto->email);
        if ($dupeError) {
            return $this->errorResponse($dupeError, Response::HTTP_CONFLICT);
        }

        $isAdmin = false;
        $securityUser = $this->getUser();
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }

        try {
            $createdOrg = $this->organizationService->registerOrganization($dto, $isAdmin);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            $createdOrg,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['org:read']]
        );
    }

    //#[Route('/api/organizations/{cif}', name: 'api_organizations_delete', methods: ['DELETE'])]
    #[Route('/{cif}', name: 'api_organizations_delete', methods: ['DELETE'])]
    public function deleteOrganization(string $cif): JsonResponse
    {
        $success = $this->organizationService->deleteOrganization($cif);
        if (!$success) {
            return $this->errorResponse('Organización no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{cif}', name: 'api_organizations_patch', methods: ['PATCH'])]
    public function updateState(string $cif, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if ($nuevoEstado) {
            try {
                $enumStatus = \App\Enum\OrganizationStatus::from(strtoupper(trim($nuevoEstado)));
            } catch (\ValueError $e) {
                return $this->errorResponse(
                    'Estado inválido. Valores permitidos: ' . implode(', ', array_map(fn($c) => $c->name, \App\Enum\OrganizationStatus::cases())),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $updatedOrg = $this->organizationService->updateState($cif, $enumStatus);
            if (!$updatedOrg) {
                return $this->errorResponse('Organización no encontrada.', Response::HTTP_NOT_FOUND);
            }

            return $this->json($updatedOrg, Response::HTTP_OK, [], ['groups' => ['org:read']]);
        }

        return $this->errorResponse('No se proporcionó estado.', Response::HTTP_BAD_REQUEST);
    }

    #[Route('/api/organizations/by-email', name: 'api_organizations_get_by_email', methods: ['GET'])]
    public function getByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        if (!$email) {
            return $this->errorResponse('El parámetro email es obligatorio.', Response::HTTP_BAD_REQUEST);
        }

        $organizacion = $this->organizationService->getByEmail($email);
        if (!$organizacion) {
            return $this->errorResponse('Organización no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($organizacion, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }

    #[Route('/{cif}', name: 'api_organizations_update', methods: ['PUT'])]
    public function update(string $cif, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $updatedOrg = $this->organizationService->updateOrganization($cif, $data);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar la organización: ' . $e->getMessage(), 500);
        }

        if (!$updatedOrg) {
            return $this->errorResponse('Organización no encontrada.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($updatedOrg, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }
}
