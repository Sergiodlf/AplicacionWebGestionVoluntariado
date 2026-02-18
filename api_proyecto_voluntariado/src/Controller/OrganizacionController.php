<?php

namespace App\Controller;

namespace App\Controller;

use App\Entity\Organizacion;
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
    private OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    #[Route('/api/organizations', name: 'api_organizations_list', methods: ['GET'])]
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

    #[Route('/api/organizations', name: 'api_organizations_create', methods: ['POST'])]
    public function createOrganization(
        Request $request, 
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            /** @var Organizacion $organizacion */
            $organizacion = $serializer->deserialize(
                $request->getContent(), 
                Organizacion::class, 
                'json',
                ['groups' => ['org:write']] 
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        // We need a DTO here really, but for now we'll stick to how the code was structured or map it?
        // The service expects a DTO. The controller deserializes to Entity. 
        // This is a disconnect. The service was using `RegistroOrganizacionDTO`.
        // Let's create the DTO from the entity manually or adjust the controller to deserialize to DTO.
        
        $data = json_decode($request->getContent(), true);
        
        $dto = new \App\Model\RegistroOrganizacionDTO();
        $dto->cif = $organizacion->getCif();
        $dto->nombre = $organizacion->getNombre();
        $dto->email = $organizacion->getEmail();
        $dto->password = $data['password'] ?? '123456'; // Fallback or required?
        $dto->direccion = $organizacion->getDireccion();
        $dto->cp = $organizacion->getCp();
        $dto->localidad = $organizacion->getLocalidad();
        $dto->descripcion = $organizacion->getDescripcion();
        $dto->contacto = $organizacion->getContacto();
        $dto->sector = $organizacion->getSector();

        // Validation logic
        // ... (This logic should ideally be in the service or a validator)
        
        // 3. VALIDACIÓN
        $errors = $validator->validate($organizacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        // Uniqueness check
        $dupeError = $this->organizationService->checkDuplicates($dto->cif, $dto->email);
        if ($dupeError) {
             return $this->json(['error' => $dupeError], Response::HTTP_CONFLICT);
        }

        $isAdmin = false;
        $securityUser = $this->getUser();
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }

        try {
            $createdOrg = $this->organizationService->registerOrganization($dto, $isAdmin);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            $createdOrg, 
            Response::HTTP_CREATED, 
            [], 
            ['groups' => ['org:read']]
        );
    }

    #[Route('/api/organizations/{cif}', name: 'api_organizations_delete', methods: ['DELETE'])]
    public function deleteOrganization(string $cif): JsonResponse
    {
        $success = $this->organizationService->deleteOrganization($cif);
        if (!$success) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/organizations/{cif}', name: 'api_organizations_patch', methods: ['PATCH'])]
    public function updateState(string $cif, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;
        
        if ($nuevoEstado) {
            $enumStatus = \App\Enum\OrganizationStatus::tryFrom(strtolower($nuevoEstado));
            if (!$enumStatus) {
                return $this->json(
                    ['error' => 'Estado inválido. Valores permitidos: ' . implode(', ', array_column(\App\Enum\OrganizationStatus::cases(), 'value'))], 
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $updatedOrg = $this->organizationService->updateState($cif, $enumStatus);
            if (!$updatedOrg) {
                 return $this->json(['error' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
            }
            
            return $this->json($updatedOrg, Response::HTTP_OK, [], ['groups' => ['org:read']]);
        }

        return $this->json(['error' => 'No se proporcionó estado.'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/api/organizations/by-email', name: 'api_organizations_get_by_email', methods: ['GET'])]
    public function getByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        if (!$email) {
            return $this->json(['error' => 'El parmetro email es obligatorio.'], Response::HTTP_BAD_REQUEST);
        }

        $organizacion = $this->organizationService->getByEmail($email);
        if (!$organizacion) {
            return $this->json(['error' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($organizacion, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }

    #[Route('/api/organizations/{cif}', name: 'api_organizations_update', methods: ['PUT'])]
    public function update(string $cif, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            $updatedOrg = $this->organizationService->updateOrganization($cif, $data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar la organización: ' . $e->getMessage()], 500);
        }

        if (!$updatedOrg) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($updatedOrg, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }
}