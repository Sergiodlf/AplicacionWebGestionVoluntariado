<?php

namespace App\Controller;

use App\Entity\Administrador;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Model\RegistroOrganizacionDTO;
use App\Model\RegistroVoluntarioDTO;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
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
    // =========================================================================
    // 1. REGISTRO DE VOLUNTARIOS (SOLUCIÓN SQL PURO)
    // =========================================================================
    #[Route('/register/voluntario', name: 'register_voluntario', methods: ['POST'])]
    public function registerVoluntario(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var RegistroVoluntarioDTO $dto */
            $dto = $serializer->deserialize($json, RegistroVoluntarioDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'Datos JSON inválidos'], 400);
        }



        // --- VALIDACIÓN DE CAMPOS OBLIGATORIOS ---
        if (!$dto->dni || !$dto->email || !$dto->nombre || !$dto->password || !$dto->zona || !$dto->ciclo || !$dto->fechaNacimiento) {
            return $this->json(['error' => 'Faltan campos obligatorios (dni, email, nombre, password, zona, ciclo, fechaNacimiento)'], 400);
        }

        // --- VALIDACIÓN DE FORMATO DE EMAIL (PV-41) ---
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Formato de correo electrónico inválido'], 400);
        }

        // --- VALIDACIÓN DE CONTRASEÑA (Mínimo 6 caracteres) ---
        if (strlen($dto->password) < 6) {
            return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        // --- VALIDACIÓN DE UNICIDAD (Vía Service) ---
        $error = $this->volunteerService->checkDuplicates($dto->dni, $dto->email);
        if ($error) {
            $status = str_contains($error, 'existe') ? 409 : 400;
            return $this->json(['error' => $error], $status);
        }

        // --- VALIDACIÓN DE NEGOCIO (Vía Service) --- (DNI, Age)
        $validationError = $this->volunteerService->validateDTO($dto);
        if ($validationError) {
            return $this->json(['error' => $validationError], 400);
        }

        // --- PRE-CHECK ADMIN --- 
        $isAdmin = false;
        $securityUser = $this->getUser();
        $currentUser = $securityUser?->getDomainUser();
        
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }
        


        // --- REGISTRO (Vía Service) ---
        try {
            $this->volunteerService->registerVolunteer($dto, $isAdmin);
        } catch (\Throwable $e) {
            // Logueamos el error completo para debug
            
            // Si es un mensaje conocido (validación o duplicado), devolvemos 400/409
            if (str_contains($e->getMessage(), 'ya está registrado')) {
                return $this->json(['error' => $e->getMessage()], 409);
            }
            
            return $this->json(['error' => 'Error al registrar voluntario', 'detalle' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Voluntario registrado correctamente' . ($isAdmin ? ' (Auto-Aceptado)' : '')], 201);
    }

    // =========================================================================
    // 2. REGISTRO DE ORGANIZACIONES
    // =========================================================================
    #[Route('/register/organizacion', name: 'register_organizacion', methods: ['POST'])]
    public function registerOrganizacion(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var RegistroOrganizacionDTO $dto */
            $dto = $serializer->deserialize($json, RegistroOrganizacionDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'Datos JSON inválidos'], 400);
        }

        // --- VALIDACIÓN DE CAMPOS OBLIGATORIOS (PV-38) ---
        if (
            empty($dto->cif) || empty($dto->nombre) || empty($dto->email) || empty($dto->password) ||
            empty($dto->direccion) || empty($dto->localidad) || empty($dto->descripcion) || 
            empty($dto->cp) || empty($dto->contacto)
        ) {
            return $this->json([
                'error' => 'Faltan campos obligatorios. Debes completar: cif, nombre, email, password, direccion, localidad, descripcion, cp, contacto'
            ], 400);
        }

        // --- VALIDACIÓN DE FORMATO DE EMAIL (PV-41) ---
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Formato de correo electrónico inválido'], 400);
        }

        // --- VALIDACIÓN DE CONTRASEÑA (Mínimo 6 caracteres) ---
        if (strlen($dto->password) < 6) {
            return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }
        
        // --- VALIDACIÓN DUP (Vía Service) ---
        $error = $this->organizationService->checkDuplicates($dto->cif, $dto->email);
        if ($error) {
            return $this->json(['error' => $error], 409);
        }

        // --- VALIDACIÓN DE NEGOCIO (Vía Service) --- (CIF)
        $validationError = $this->organizationService->validateDTO($dto);
        if ($validationError) {
            return $this->json(['error' => $validationError], 400);
        }

        // --- PRE-CHECK ADMIN ---
        $isAdmin = false;
        $securityUser = $this->getUser();
        $currentUser = $securityUser?->getDomainUser();
        
        if ($securityUser && in_array('ROLE_ADMIN', $securityUser->getRoles())) {
            $isAdmin = true;
        }

        // --- REGISTRO (Vía Service) ---
        try {
            $this->organizationService->registerOrganization($dto, $isAdmin);
        } catch (\Throwable $e) {

            
            if (str_contains($e->getMessage(), 'ya está registrado')) {
                return $this->json(['error' => $e->getMessage()], 409);
            }
            return $this->json(['error' => 'Error al guardar organización', 'mensaje_tecnico' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Organización creada correctamente'], 201);
    }

    // =========================================================================
    // 3. LOGIN UNIFICADO (PROXY FIREBASE + VERIFICACIÓN DB LOCAL)
    // =========================================================================
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email y contraseña requeridos'], 400);
        }

        // Obtener API KEY de variables de entorno
        // Robust check: $_ENV -> $_SERVER -> getenv
        $apiKey = $_ENV['FIREBASE_API_KEY'] ?? $_SERVER['FIREBASE_API_KEY'] ?? getenv('FIREBASE_API_KEY');
        
        if (!$apiKey) {
            // Debugging: Log available keys to help diagnose why it's missing
            $envKeys = implode(', ', array_keys($_ENV));
            $serverKeys = implode(', ', array_keys($_SERVER));
            error_log("FIREBASE_API_KEY missing. ENV keys: [$envKeys]. SERVER keys: [$serverKeys]");
            
            return $this->json(['error' => 'Error de configuración en servidor: Falta FIREBASE_API_KEY'], 500);
        }

        try {
            // 1. Autenticar contra Firebase (REST API)
            
            $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . $apiKey, [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'returnSecureToken' => true
                ],
                'headers' => [
                    // Use the oficial Auth Domain which is always whitelisted
                    'Referer' => 'https://proyecto-voluntariado-9c2d5.firebaseapp.com'
                ]
            ]);

            // Si falla (4xx), lanzará excepción ClientExceptionInterface
            $firebaseData = $response->toArray();
            
            // 2. Verificar existencia en Base de Datos Local
            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($email);
            } catch (UserNotFoundException $e) {
                 return $this->json([
                     'error' => 'Inconsistencia de cuenta.',
                     'detalle' => 'El usuario existe en el sistema de autenticación pero no tiene perfil en la base de datos local.'
                 ], 400, ['Access-Control-Allow-Origin' => '*']);
            }

            // --- FIX: Desempaquetar SecurityUser ---
            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

            // 2b. Validar estado del usuario (NO ADMINISTRADORES)
            if ($localUser instanceof Voluntario) {
                $estado = $localUser->getEstadoVoluntario();
                
                // Permitimos ACEPTADO y LIBRE (que es el estado de las cuentas de prueba)
                if ($estado !== 'ACEPTADO' && $estado !== 'LIBRE') {
                    if ($estado === 'PENDIENTE') {
                        return $this->json([
                            'error' => 'Cuenta pendiente de aprobación',
                            'message' => 'Tu cuenta está pendiente de aprobación. Por favor, espera a que un administrador revise tu solicitud.'
                        ], 403);
                    } elseif ($estado === 'RECHAZADO') {
                        return $this->json([
                            'error' => 'Cuenta rechazada',
                            'message' => 'Tu cuenta ha sido rechazada. Contacta con el administrador para más información.'
                        ], 403);
                    } elseif ($estado === 'BAJA') {
                        return $this->json([
                            'error' => 'Cuenta desactivada',
                            'message' => 'Tu cuenta ha sido desactivada. Contacta con el administrador para más información.'
                        ], 403);
                    } else {
                        return $this->json([
                            'error' => 'Acceso denegado',
                            'message' => 'Tu cuenta no está activa. Estado: ' . $estado
                        ], 403);
                    }
                }
            } elseif ($localUser instanceof Organizacion) {
                $estado = strtolower($localUser->getEstado());
                
                if ($estado !== 'aprobado' && $estado !== 'aceptada') {
                    if ($estado === 'pendiente') {
                        return $this->json([
                            'error' => 'Organización pendiente de aprobación',
                            'message' => 'Tu organización está pendiente de aprobación. Por favor, espera a que un administrador revise tu solicitud.'
                        ], 403);
                    } elseif ($estado === 'rechazada' || $estado === 'rechazado') {
                        return $this->json([
                            'error' => 'Organización rechazada',
                            'message' => 'Tu organización ha sido rechazada. Contacta con el administrador para más información.'
                        ], 403);
                    } else {
                        return $this->json([
                            'error' => 'Acceso denegado',
                            'message' => 'Tu organización no está activa. Estado: ' . $estado
                        ], 403);
                    }
                }
            }
            // Administradores siempre tienen acceso

            // 2c. Obtener estado de verificación de email desde Firebase Admin SDK
            $emailVerified = false;
            try {
                $firebaseUser = $this->firebaseAuth->getUser($firebaseData['localId']);
                $emailVerified = $firebaseUser->emailVerified;
            } catch (\Exception $e) {
                error_log("Error getting user verification status: " . $e->getMessage());
            }

            // 3. Login Exitoso
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
            // Manejo de errores de Firebase (400 Bad Request)
            $responseBody = $e->getResponse()->getContent(false);
            
            try {
                $errorContent = $e->getResponse()->toArray(false);
                $msg = $errorContent['error']['message'] ?? 'Error de autenticación';
            } catch (\Exception $decodeEx) {
                $msg = 'Error desconocido de autenticación';
            }
            
            if (in_array($msg, ['EMAIL_NOT_FOUND', 'INVALID_PASSWORD', 'INVALID_LOGIN_CREDENTIALS'])) {
                // TEMPORARY DEBUG: Expose specific error
                return $this->json(['error' => 'Credenciales inválidas (' . $msg . ')'], 401);
            }
            if ($msg === 'USER_DISABLED') {
                return $this->json(['error' => 'Usuario deshabilitado'], 403);
            }
            if ($msg === 'TOO_MANY_ATTEMPTS_TRY_LATER') {
                return $this->json(['error' => 'Demasiados intentos. Inténtalo más tarde.'], 429);
            }

            return $this->json(['error' => 'Error de Firebase: ' . $msg], 400);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    private function getUserRole($user): string
    {
        if ($user instanceof \App\Entity\Administrador) return 'admin';
        if ($user instanceof \App\Entity\Voluntario) return 'voluntario';
        if ($user instanceof \App\Entity\Organizacion) return 'organizacion';
        return 'unknown';
    }

    // =========================================================================
    // 3.5 LOGIN CON GOOGLE (Verificación de Token + Check DB)
    // =========================================================================
    #[Route('/login/google', name: 'login_google', methods: ['POST'])]
    public function loginGoogle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token de Firebase requerido'], 400);
        }

        try {
            // 1. Verificar el token con Firebase Admin SDK
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $claims = $verifiedIdToken->claims();
            $email = $claims->get('email');
            $uid = $claims->get('sub');
            $emailVerified = $claims->get('email_verified');

            if (!$email) {
                return $this->json(['error' => 'El token no contiene un email válido'], 400);
            }

            // 2. Verificar existencia en Base de Datos Local
            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($email);
            } catch (UserNotFoundException $e) {
                 return $this->json([
                     'error' => 'Usuario no registrado',
                     'message' => 'No existe una cuenta vinculada a este correo de Google. Por favor, regístrate primero.',
                     'email' => $email, 
                     'uid' => $uid
                 ], 404);
            }

            // --- FIX: Desempaquetar SecurityUser ---
            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

            // 2b. Validar estado del usuario (NO ADMINISTRADORES)
            if ($localUser instanceof Voluntario) {
                $estado = $localUser->getEstadoVoluntario();
                // Permitimos ACEPTADO y LIBRE (que es el estado de las cuentas de prueba)
                if ($estado !== 'ACEPTADO' && $estado !== 'LIBRE') {
                    if ($estado === 'PENDIENTE') {
                        return $this->json([
                            'error' => 'Cuenta pendiente de aprobación',
                            'message' => 'Tu cuenta está pendiente de aprobación. Por favor, espera a que un administrador revise tu solicitud.'
                        ], 403);
                    } elseif ($estado === 'RECHAZADO') {
                        return $this->json([
                            'error' => 'Cuenta rechazada',
                            'message' => 'Tu cuenta ha sido rechazada. Contacta con el administrador para más información.'
                        ], 403);
                    } elseif ($estado === 'BAJA') {
                        return $this->json([
                            'error' => 'Cuenta desactivada',
                            'message' => 'Tu cuenta ha sido desactivada. Contacta con el administrador para más información.'
                        ], 403);
                    } else {
                        return $this->json([
                            'error' => 'Acceso denegado',
                            'message' => 'Tu cuenta no está activa. Estado: ' . $estado
                        ], 403);
                    }
                }
            } elseif ($localUser instanceof Organizacion) {
                $estado = strtolower($localUser->getEstado());
                if ($estado !== 'aprobado' && $estado !== 'aceptada') {
                    if ($estado === 'pendiente') {
                        return $this->json([
                            'error' => 'Organización pendiente de aprobación',
                            'message' => 'Tu organización está pendiente de aprobación. Por favor, espera a que un administrador revise tu solicitud.'
                        ], 403);
                    } elseif ($estado === 'rechazada' || $estado === 'rechazado') {
                        return $this->json([
                            'error' => 'Organización rechazada',
                            'message' => 'Tu organización ha sido rechazada. Contacta con el administrador para más información.'
                        ], 403);
                    } else {
                        return $this->json([
                            'error' => 'Acceso denegado',
                            'message' => 'Tu organización no está activa. Estado: ' . $estado
                        ], 403);
                    }
                }
            }
            // Administradores siempre tienen acceso

            // 3. Login Exitoso 
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
            return $this->json(['error' => 'Token inválido o expirado: ' . $e->getMessage()], 401);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Error interno al verificar Google Login: ' . $e->getMessage()], 500);
        }
    }
    
    // =========================================================================
    // 4. FORGOT PASSWORD (UNIFICADO)
    // =========================================================================
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request, 
        \App\Service\NotificationService $notificationService // Injected here or constructor
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email es obligatorio'], 400);
        }

        try {
            // 1. Generate Link via Firebase
            $link = $this->firebaseAuth->getPasswordResetLink($email);

            // 2. Send Email via custom Mailer
            // We use the injected $notificationService (need to update constructor or use container)
            // Ideally, inject NotificationService in AuthController constructor.
            // But since I can't easily change constructor in replace_file_content without context, 
            // I'll assume it's available or I'll add it to methods arguments if Symfony supports it (it does).
            
            $notificationService->sendEmail(
                $email,
                'Reset Password - Gestión Voluntariado',
                sprintf(
                    '<p>Has solicitado restablecer tu contraseña.</p><p>Haz clic aquí para cambiarla: <a href="%s">Restablecer Contraseña</a></p>', 
                    $link
                )
            );

            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);

        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            // Security: Don't reveal if user exists or not, but for UX maybe we simulate success
            // or just return success message regardless.
            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()], 500);
        }
    }
    // =========================================================================
    // 5. OBTENER PERFIL (UNIFICADO)
    // =========================================================================
    // =========================================================================
    // 5. OBTENER PERFIL (UNIFICADO)
    // =========================================================================
    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function getProfile(
        SerializerInterface $serializer
    ): JsonResponse
    {
        try {
            // El usuario ya viene autenticado por el Token Handler (UnifiedUserProvider)
            $securityUser = $this->getUser();
            $user = $securityUser?->getDomainUser();

            if (!$user) {
                return $this->json(['error' => 'Usuario no autenticado o token inválido'], 401);
            }

            // --- 0. Caso Admin (Real) ---
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

            // --- 1. Caso Voluntario ---
            if ($user instanceof Voluntario) {
                // (El backdoor antiguo se puede eliminar o dejar por seguridad, pero AdminUser tiene prioridad)

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
                        'estado_voluntario' => $user->getEstadoVoluntario(),
                        'disponibilidad' => $user->getDisponibilidad(),
                        'ciclo' => $user->getCiclo() ? (string)$user->getCiclo() : null,
                    ]
                ]);
            }

            // --- 2. Caso Organización ---
            if ($user instanceof Organizacion) {
                // Serialización automática
                $jsonOrg = $serializer->serialize($user, 'json', ['groups' => ['org:read']]);
                $arrayOrg = json_decode($jsonOrg, true);
                
                // Eliminamos las actividades para aligerar la respuesta (solo datos editables)
                if (isset($arrayOrg['actividades'])) {
                    unset($arrayOrg['actividades']);
                }

                return $this->json([
                    'tipo' => 'organizacion',
                    'datos' => $arrayOrg
                ]);
            }

            return $this->json(['error' => 'Tipo de usuario desconocido: ' . get_class($user)], 500);

        } catch (\Throwable $e) {

            return $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 6. ACTUALIZAR PERFIL (UNIFICADO)
    // =========================================================================
    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $securityUser = $this->getUser();
        $user = $securityUser?->getDomainUser();

        if (!$user) {
            return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        // RELOAD USER to ensure it is managed by the current EntityManager
        // (Fixes issue where updates are not persisted)
        if ($user instanceof Voluntario) {
            $user = $em->getRepository(Voluntario::class)->find($user->getDni());
        } elseif ($user instanceof Organizacion) {
            $user = $em->getRepository(Organizacion::class)->find($user->getCif());
        }

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado en base de datos'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // --- 1. Caso Voluntario ---
        if ($user instanceof Voluntario) {
            if (isset($data['nombre'])) $user->setNombre($data['nombre']);
            if (isset($data['apellido1'])) $user->setApellido1($data['apellido1']);
            if (isset($data['apellido2'])) $user->setApellido2($data['apellido2']);
            if (isset($data['zona'])) $user->setZona($data['zona']);
            if (isset($data['experiencia'])) $user->setExperiencia($data['experiencia']);
            if (isset($data['coche'])) $user->setCoche($data['coche']);
            if (isset($data['disponibilidad']) && is_array($data['disponibilidad'])) $user->setDisponibilidad(array_values($data['disponibilidad'])); // Array
            if (isset($data['idiomas']) && is_array($data['idiomas'])) $user->setIdiomas(array_values($data['idiomas'])); // Array

            // Relaciones ManyToMany (Habilidades)
            if (isset($data['habilidades']) && is_array($data['habilidades'])) {
                $user->getHabilidades()->clear();
                $repoHab = $em->getRepository(\App\Entity\Habilidad::class);
                foreach ($data['habilidades'] as $item) {
                     $habilidad = null;
                     
                     // 1. Si es un array con ID
                     if (is_array($item) && isset($item['id'])) {
                         $habilidad = $repoHab->find($item['id']);
                     }
                     // 2. Si es numérico (ID directo)
                     elseif (is_numeric($item)) {
                         $habilidad = $repoHab->find($item);
                     }
                     // 3. Si es string (Nombre)
                     elseif (is_string($item)) {
                         $habilidad = $repoHab->findOneBy(['nombre' => $item]);
                     }

                     if ($habilidad) {
                         $user->addHabilidad($habilidad);
                     }
                }
            }

            // Relaciones ManyToMany (Intereses)
            if (isset($data['intereses']) && is_array($data['intereses'])) {
                $user->getIntereses()->clear();
                $repoInt = $em->getRepository(\App\Entity\Interes::class);
                foreach ($data['intereses'] as $item) {
                    $interes = null;

                     // 1. Si es un array con ID
                     if (is_array($item) && isset($item['id'])) {
                         $interes = $repoInt->find($item['id']);
                     }
                     // 2. Si es numérico (ID directo)
                     elseif (is_numeric($item)) {
                         $interes = $repoInt->find($item);
                     }
                     // 3. Si es string (Nombre)
                     elseif (is_string($item)) {
                         $interes = $repoInt->findOneBy(['nombre' => $item]);
                     }

                     if ($interes) {
                         $user->addInterese($interes);
                     }
                }
            }
            
            // Ciclo (Opcional)
            // Ciclo
            if (isset($data['ciclo'])) {
                 $cicloData = $data['ciclo'];
                 $repoCiclo = $em->getRepository(\App\Entity\Ciclo::class);
                 $cicloObj = null;

                 if (is_array($cicloData) && isset($cicloData['nombre']) && isset($cicloData['curso'])) {
                     $cicloObj = $repoCiclo->findOneBy([
                         'nombre' => $cicloData['nombre'], 
                         'curso' => $cicloData['curso']
                     ]);
                 } elseif (is_string($cicloData)) {
                     // Try to match by nombre only (might be ambiguous) or parse string
                     $parts = [];
                     if (preg_match('/^(.*)\s\((\d+)º\)$/', $cicloData, $parts)) {
                         $nombre = trim($parts[1]);
                         $curso = (int)$parts[2];
                         $cicloObj = $repoCiclo->findOneBy(['nombre' => $nombre, 'curso' => $curso]);
                     } else {
                        // Fallback: search by name only (take first)
                        $cicloObj = $repoCiclo->findOneBy(['nombre' => $cicloData]);
                     }
                 }

                 if ($cicloObj) {
                     $user->setCiclo($cicloObj);
                 }
            }



            // FCM Token
            if (isset($data['fcmToken'])) {
                $user->setFcmToken($data['fcmToken']);
            }

        } 
        // --- 2. Caso Organización ---
        elseif ($user instanceof Organizacion) {
            if (isset($data['nombre'])) $user->setNombre($data['nombre']);
            // if (isset($data['email'])) $user->setEmail($data['email']); // Mejor no permitir cambio de email/ID login fácilmente
            if (isset($data['sector'])) $user->setSector($data['sector']);
            if (isset($data['direccion'])) $user->setDireccion($data['direccion']);
            if (isset($data['localidad'])) $user->setLocalidad($data['localidad']);
            if (isset($data['cp'])) $user->setCp($data['cp']);
            if (isset($data['descripcion'])) $user->setDescripcion($data['descripcion']);
            if (isset($data['contacto'])) $user->setContacto($data['contacto']);
            
            // FCM Token
            if (isset($data['fcmToken'])) {
                $user->setFcmToken($data['fcmToken']);
            }
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar perfil', 'detalle' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Perfil actualizado correctamente']);
    }
}