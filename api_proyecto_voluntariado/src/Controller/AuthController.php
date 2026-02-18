<?php

namespace App\Controller;

use App\Entity\Administrador;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Model\RegistroOrganizacionDTO;
use App\Model\RegistroVoluntarioDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Security\UnifiedUserProvider;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

use App\Service\VolunteerService;
use Kreait\Firebase\Contract\Auth;
use App\Service\OrganizationService;
use App\Enum\VolunteerStatus;
use App\Enum\OrganizationStatus;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    use ApiErrorTrait;

    private $volunteerService;
    private $organizationService;
    private $firebaseAuth;
    private $notificationService;
    private $httpClient;
    private $unifiedUserProvider;

    public function __construct(
        VolunteerService $volunteerService, 
        OrganizationService $organizationService, 
        Auth $firebaseAuth,
        \App\Service\NotificationService $notificationService,
        HttpClientInterface $httpClient,
        UnifiedUserProvider $unifiedUserProvider
    )
    {
        $this->volunteerService = $volunteerService;
        $this->organizationService = $organizationService;
        $this->firebaseAuth = $firebaseAuth;
        $this->notificationService = $notificationService;
        $this->httpClient = $httpClient;
        $this->unifiedUserProvider = $unifiedUserProvider;
    }

    // --- Registro de voluntarios ---
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

        // --- VALIDACIÓN DE CAMPOS OBLIGATORIOS ---
        if (!$dto->dni || !$dto->email || !$dto->nombre || !$dto->password || !$dto->zona || !$dto->ciclo || !$dto->fechaNacimiento) {
            return $this->errorResponse('Faltan campos obligatorios (dni, email, nombre, password, zona, ciclo, fechaNacimiento)', 400);
        }

        // --- VALIDACIÓN DE FORMATO DE EMAIL (PV-41) ---
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Formato de correo electrónico inválido', 400);
        }

        // --- VALIDACIÓN DE CONTRASEÑA (Mínimo 6 caracteres) ---
        if (strlen($dto->password) < 6) {
            return $this->errorResponse('La contraseña debe tener al menos 6 caracteres', 400);
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


    // --- Registro de organizaciones ---
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

        // --- VALIDACIÓN DE CAMPOS OBLIGATORIOS (PV-38) ---
        if (
            empty($dto->cif) || empty($dto->nombre) || empty($dto->email) || empty($dto->password) ||
            empty($dto->direccion) || empty($dto->localidad) || empty($dto->descripcion) || 
            empty($dto->cp) || empty($dto->contacto)
        ) {
            return $this->errorResponse('Faltan campos obligatorios. Debes completar: cif, nombre, email, password, direccion, localidad, descripcion, cp, contacto', 400);
        }

        // --- VALIDACIÓN DE FORMATO DE EMAIL (PV-41) ---
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Formato de correo electrónico inválido', 400);
        }

        // --- VALIDACIÓN DE CONTRASEÑA (Mínimo 6 caracteres) ---
        if (strlen($dto->password) < 6) {
            return $this->errorResponse('La contraseña debe tener al menos 6 caracteres', 400);
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

    // --- Login unificado ---
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {

        
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->errorResponse('Email y contraseña requeridos', 400);
        }

        $apiKey = $_ENV['FIREBASE_API_KEY'] ?? $_SERVER['FIREBASE_API_KEY'] ?? getenv('FIREBASE_API_KEY');
        
        if (!$apiKey) {
            return $this->errorResponse('Error de configuración en servidor: Falta FIREBASE_API_KEY', 500);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . $apiKey, [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'returnSecureToken' => true
                ],
                'headers' => [
                    'Referer' => 'https://proyecto-voluntariado-9c2d5.firebaseapp.com'
                ]
            ]);

            $firebaseData = $response->toArray();
            
            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($email);
            } catch (UserNotFoundException $e) {
                 return $this->errorResponse('Inconsistencia de cuenta. El usuario existe en el sistema de autenticación pero no tiene perfil en la base de datos local.', 404);
            }

            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

            // Validar estado del usuario (excepto administradores)
            if ($localUser instanceof Voluntario) {
                $estado = $localUser->getEstadoVoluntario();
                if ($estado !== VolunteerStatus::ACEPTADO && $estado !== VolunteerStatus::LIBRE) {
                    if ($estado === VolunteerStatus::PENDIENTE) {
                        return $this->errorResponse('Tu cuenta está pendiente de aprobación.', 403);
                    } elseif ($estado === VolunteerStatus::RECHAZADO) {
                        return $this->errorResponse('Tu cuenta ha sido rechazada.', 403);
                    } else {
                        // Safe fallback for value
                        $val = $estado ? $estado->value : 'NULL';
                        return $this->errorResponse('Tu cuenta no está activa. Estado: ' . $val, 403);
                    }
                }
            } elseif ($localUser instanceof Organizacion) {
                $estado = $localUser->getEstado();
                if ($estado !== OrganizationStatus::APROBADO) {
                    if ($estado === OrganizationStatus::PENDIENTE) {
                        return $this->errorResponse('Tu organización está pendiente de aprobación.', 403);
                    } elseif ($estado === OrganizationStatus::RECHAZADO) {
                        return $this->errorResponse('Tu organización ha sido rechazada.', 403);
                    } else {
                        return $this->errorResponse('Tu organización no está activa. Estado: ' . ($estado?->value ?? 'NULL'), 403);
                    }
                }
            }

            $emailVerified = false;
            try {
                $firebaseUser = $this->firebaseAuth->getUser($firebaseData['localId']);
                $emailVerified = $firebaseUser->emailVerified;
            } catch (\Exception $e) {
                // No interrumpir login si falla la consulta de verificación
            }

            return $this->json([
                'message' => 'Login correcto',
                'token' => $firebaseData['idToken'],
                'refreshToken' => $firebaseData['refreshToken'],
                'expiresIn' => $firebaseData['expiresIn'],
                'localId' => $firebaseData['localId'],
                'email' => $firebaseData['email'],
                'emailVerified' => $emailVerified,
                'rol' => $this->getUserRole($localUser) 
            ]);

        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            try {
                $errorContent = $e->getResponse()->toArray(false);
                $msg = $errorContent['error']['message'] ?? 'Error de autenticación';
            } catch (\Exception $decodeEx) {
                $msg = 'Error desconocido de autenticación';
            }
            if (in_array($msg, ['EMAIL_NOT_FOUND', 'INVALID_PASSWORD', 'INVALID_LOGIN_CREDENTIALS'])) {
                return $this->errorResponse('Credenciales inválidas (' . $msg . ')', 401);
            }
            return $this->errorResponse('Error de Firebase: ' . $msg, 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error interno: ' . $e->getMessage(), 500);
        }
    }

    private function getUserRole($user): string
    {
        if ($user instanceof \App\Entity\Administrador) return 'admin';
        if ($user instanceof \App\Entity\Voluntario) return 'voluntario';
        if ($user instanceof \App\Entity\Organizacion) return 'organizacion';
        return 'unknown';
    }

    #[Route('/login/google', name: 'login_google', methods: ['POST'])]
    public function loginGoogle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->errorResponse('Token de Firebase requerido', 400);
        }

        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $claims = $verifiedIdToken->claims();
            $email = $claims->get('email');
            $uid = $claims->get('sub');
            $emailVerified = $claims->get('email_verified');

            if (!$email) {
                return $this->errorResponse('El token no contiene un email válido', 400);
            }

            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($email);
            } catch (UserNotFoundException $e) {
                 return $this->errorResponse('No existe una cuenta vinculada a este correo de Google. Por favor, regístrate primero.', 404, ['email' => $email, 'uid' => $uid]);
            }

            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

             // 2b. Validar estado del usuario (NO ADMINISTRADORES)
             // 2b. Validar estado del usuario (NO ADMINISTRADORES)
            if ($localUser instanceof Voluntario) {
                $estado = $localUser->getEstadoVoluntario();
                if ($estado !== VolunteerStatus::ACEPTADO && $estado !== VolunteerStatus::LIBRE) {
                    if ($estado === VolunteerStatus::PENDIENTE) {
                        return $this->errorResponse('Tu cuenta está pendiente de aprobación.', 403);
                    } elseif ($estado === VolunteerStatus::RECHAZADO) {
                        return $this->errorResponse('Tu cuenta ha sido rechazada.', 403);
                    } else {
                        $val = $estado ? $estado->value : 'NULL';
                        return $this->errorResponse('Tu cuenta no está activa. Estado: ' . $val, 403);
                    }
                }
            } elseif ($localUser instanceof Organizacion) {
                $estado = $localUser->getEstado();
                if ($estado !== OrganizationStatus::APROBADO) {
                    if ($estado === OrganizationStatus::PENDIENTE) {
                        return $this->errorResponse('Tu organización está pendiente de aprobación.', 403);
                    } elseif ($estado === OrganizationStatus::RECHAZADO) {
                         return $this->errorResponse('Tu organización ha sido rechazada.', 403);
                    } else {
                        return $this->errorResponse('Tu organización no está activa. Estado: ' . ($estado?->value ?? 'NULL'), 403);
                    }
                }
            }

            return $this->json([
                'message' => 'Login correcto (Google)',
                'token' => $token, 
                'refreshToken' => null, 
                'expiresIn' => 3600, 
                'localId' => $uid,
                'email' => $email,
                'emailVerified' => $emailVerified,
                'rol' => $this->getUserRole($localUser)
            ]);

        } catch (FailedToVerifyToken $e) {
            return $this->errorResponse('Token inválido o expirado: ' . $e->getMessage(), 401);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error interno al verificar Google Login: ' . $e->getMessage(), 500);
        }
    }
    
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->errorResponse('Email es obligatorio', 400);
        }

        try {
            $link = $this->firebaseAuth->getPasswordResetLink($email);

            $this->notificationService->sendEmail(
                $email,
                'Reset Password - Gestión Voluntariado',
                sprintf(
                    '<p>Has solicitado restablecer tu contraseña.</p><p>Haz clic aquí para cambiarla: <a href="%s">Restablecer Contraseña</a></p>', 
                    $link
                )
            );

            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);

        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al procesar la solicitud: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function getProfile(SerializerInterface $serializer): JsonResponse
    {
        try {
            $securityUser = $this->getUser();
            $user = $securityUser?->getDomainUser();

            if (!$user) {
                return $this->errorResponse('Usuario no autenticado o token inválido', 401);
            }

            if ($user instanceof Administrador) {
                 return $this->json([
                    'tipo' => 'admin',
                    'datos' => [
                        'dni' => 'ADMIN-' . $user->getId(),
                        'nombre' => $user->getNombre() ?? 'Administrador',
                        'correo' => $user->getEmail(),
                        'zona' => 'Global'
                    ]
                ]);
            }

            if ($user instanceof Voluntario) {
                return $this->json([
                    'tipo' => 'voluntario',
                    'datos' => [
                        'dni' => $user->getDni(),
                        'nombre' => $user->getNombre(),
                        'apellido1' => $user->getApellido1(),
                        'apellido2' => $user->getApellido2(),
                        'correo' => $user->getCorreo(),
                        'zona' => $user->getZona(),
                        'fechaNacimiento' => $user->getFechaNacimiento() ? $user->getFechaNacimiento()->format('Y-m-d') : null,
                        'experiencia' => $user->getExperiencia(),
                        'coche' => $user->isCoche(),
                        'habilidades' => $user->getHabilidades()->map(fn($h) => ['id' => $h->getId(), 'nombre' => $h->getNombre()])->toArray(),
                        'intereses' => $user->getIntereses()->map(fn($i) => ['id' => $i->getId(), 'nombre' => $i->getNombre()])->toArray(),
                        'idiomas' => $user->getIdiomas(),
                        'estado_voluntario' => $user->getEstadoVoluntario()?->value,
                        'disponibilidad' => $user->getDisponibilidad(),
                        'ciclo' => $user->getCiclo() ? (string)$user->getCiclo() : null,
                    ]
                ]);
            }

            if ($user instanceof Organizacion) {
                $jsonOrg = $serializer->serialize($user, 'json', ['groups' => ['org:read']]);
                $arrayOrg = json_decode($jsonOrg, true);
                if (isset($arrayOrg['actividades'])) {
                    unset($arrayOrg['actividades']);
                }
                return $this->json([
                    'tipo' => 'organizacion',
                    'datos' => $arrayOrg
                ]);
            }

            return $this->errorResponse('Tipo de usuario desconocido: ' . get_class($user), 500);

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
                // RELOAD USER to ensure it is managed
                $userManaged = $this->volunteerService->getById($user->getDni());
                if (!$userManaged) return $this->errorResponse('Usuario no encontrado', 404);
                
                $this->volunteerService->updateProfile($userManaged, $data);
            } 
            elseif ($user instanceof Organizacion) {
                // RELOAD USER to ensure it is managed
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