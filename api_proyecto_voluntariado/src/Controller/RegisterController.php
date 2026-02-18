<?php

namespace App\Controller;

use App\Model\RegistroOrganizacionDTO;
use App\Model\RegistroVoluntarioDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\VolunteerService;
use App\Service\OrganizationService;

/**
 * S-1: Controller dedicado exclusivamente al registro de usuarios.
 */
#[Route('/api/auth', name: 'api_auth_')]
class RegisterController extends AbstractController
{
    use ApiErrorTrait;

    public function __construct(
        private VolunteerService $volunteerService,
        private OrganizationService $organizationService,
        private \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ) {}

    #[Route('/register/voluntario', name: 'register_voluntario', methods: ['POST'])]
    public function registerVoluntario(
        Request $request,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var RegistroVoluntarioDTO $dto */
            $dto = $serializer->deserialize($json, RegistroVoluntarioDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->errorResponse('Datos JSON inválidos', 400);
        }

        // --- VALIDACIÓN (V-1) ---
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->errorResponse('Errores de validación', 400, $errorMessages);
        }

        // --- VALIDACIÓN DE UNICIDAD (Vía Service) ---
        $error = $this->volunteerService->checkDuplicates($dto->dni, $dto->email);
        if ($error) {
            $status = str_contains($error, 'existe') ? 409 : 400;
            return $this->errorResponse($error, $status);
        }

        // --- VALIDACIÓN DE NEGOCIO (Vía Service) --- (DNI, Age)
        $validationError = $this->volunteerService->validateDTO($dto);
        if ($validationError) {
            return $this->errorResponse($validationError, 400);
        }

        // --- PRE-CHECK ADMIN --- 
        $isAdmin = false;
        $securityUser = $this->getUser();
        
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }

        // --- REGISTRO (Vía Service) ---
        try {
            $this->volunteerService->registerVolunteer($dto, $isAdmin);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'ya está registrado')) {
                return $this->errorResponse($e->getMessage(), 409);
            }
            return $this->errorResponse('Error al registrar voluntario', 500, $e->getMessage());
        }

        return $this->json(['message' => 'Voluntario registrado correctamente' . ($isAdmin ? ' (Auto-Aceptado)' : '')], 201);
    }

    #[Route('/register/organizacion', name: 'register_organizacion', methods: ['POST'])]
    public function registerOrganizacion(
        Request $request,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var RegistroOrganizacionDTO $dto */
            $dto = $serializer->deserialize($json, RegistroOrganizacionDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->errorResponse('Datos JSON inválidos', 400);
        }

        // --- VALIDACIÓN (V-1) ---
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->errorResponse('Errores de validación', 400, $errorMessages);
        }
        
        // --- VALIDACIÓN DUP (Vía Service) ---
        $error = $this->organizationService->checkDuplicates($dto->cif, $dto->email);
        if ($error) {
            return $this->errorResponse($error, 409);
        }

        // --- VALIDACIÓN DE NEGOCIO (Vía Service) --- (CIF)
        $validationError = $this->organizationService->validateDTO($dto);
        if ($validationError) {
            return $this->errorResponse($validationError, 400);
        }

        // --- PRE-CHECK ADMIN ---
        $isAdmin = false;
        $securityUser = $this->getUser();
        
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }

        // --- REGISTRO (Vía Service) ---
        try {
            $this->organizationService->registerOrganization($dto, $isAdmin);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'ya está registrado')) {
                return $this->errorResponse($e->getMessage(), 409);
            }
            return $this->errorResponse('Error al guardar organización', 500, $e->getMessage());
        }

        return $this->json(['message' => 'Organización creada correctamente'], 201);
    }
}
