<?php

namespace App\Controller;

use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Model\RegistroOrganizacionDTO;
use App\Model\RegistroVoluntarioDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use App\Service\VolunteerService;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

use App\Service\OrganizationService;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private $volunteerService;
    private $organizationService; // INJECTED
    private $firebaseAuth;

    public function __construct(VolunteerService $volunteerService, OrganizationService $organizationService, Auth $firebaseAuth)
    {
        $this->volunteerService = $volunteerService;
        $this->organizationService = $organizationService;
        $this->firebaseAuth = $firebaseAuth;
    }
    // =========================================================================
    // 1. REGISTRO DE VOLUNTARIOS (SOLUCIÓN SQL PURO)
    // =========================================================================
    #[Route('/register/voluntario', name: 'register_voluntario', methods: ['POST'])]
    public function registerVoluntario(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
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

        // --- VALIDACIÓN DE UNICIDAD (Vía Service) ---
        $error = $this->volunteerService->checkDuplicates($dto->dni, $dto->email);
        if ($error) {
            $status = str_contains($error, 'existe') ? 409 : 400;
            return $this->json(['error' => $error], $status);
        }

        // --- PRE-CHECK ADMIN --- 
        $currentUser = $this->getUser();
        $isAdmin = false;
        
        $logMsg = "--- New Register Request ---\n";
        $logMsg .= "RegisterVoluntario: Checking Admin Status...\n";

        if ($currentUser) {
            $logMsg .= "RegisterVoluntario: User found via getUser(): " . $currentUser->getUserIdentifier() . "\n";
            if ($currentUser instanceof \App\Security\User\AdminUser || (method_exists($currentUser, 'getRoles') && in_array('ROLE_ADMIN', $currentUser->getRoles()))) {
                $isAdmin = true;
                $logMsg .= "RegisterVoluntario: User IS Admin (via object check).\n";
            }
        } else {
            $logMsg .= "RegisterVoluntario: getUser() is null. Checking headers manually.\n";
            // Si getUser() es null (ruta pública), verificamos manualmente el token si existe
            $authHeader = $request->headers->get('Authorization');
            $logMsg .= "RegisterVoluntario: Authorization Header: " . ($authHeader ? "PRESENT" : "MISSING") . "\n";
            
            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                try {
                    $token = $matches[1];
                    $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
                    $claims = $verifiedIdToken->claims();
                    $email = (string) $claims->get('email');
                    
                    $logMsg .= "RegisterVoluntario: Manual Token Email: $email\n";
                    $logMsg .= "RegisterVoluntario: Claims: " . json_encode($claims->all()) . "\n";

                    if (($claims->has('admin') && $claims->get('admin') === true) || 
                        ($claims->has('rol') && $claims->get('rol') === 'admin') ||
                        $email === 'admin@admin.com' || $email === 'adminTest5666@gmail.com') {
                        $isAdmin = true;
                        $logMsg .= "RegisterVoluntario: User IS Admin (via manual token check).\n";
                    } else {
                         $logMsg .= "RegisterVoluntario: User is NOT Admin (claims mismatch).\n";
                    }
                } catch (\Throwable $e) {
                    $logMsg .= "RegisterVoluntario: Token Verification Failed: " . $e->getMessage() . "\n";
                }
            }
        }
        
        $logMsg .= "RegisterVoluntario: Final isAdmin decision: " . ($isAdmin ? "TRUE" : "FALSE") . "\n";
        file_put_contents('debug_auth.txt', $logMsg, FILE_APPEND);

        // --- REGISTRO (Vía Service) ---
        try {
            $this->volunteerService->registerVolunteer($dto, $isAdmin);
        } catch (\Throwable $e) {
            // Logueamos el error completo para debug
            file_put_contents('last_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
            
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
        UserPasswordHasherInterface $passwordHasher,
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
        
        // --- VALIDACIÓN DUP (Vía Service) ---
        $error = $this->organizationService->checkDuplicates($dto->cif, $dto->email);
        if ($error) {
            return $this->json(['error' => $error], 409);
        }

        // --- PRE-CHECK ADMIN ---
        $currentUser = $this->getUser();
        $isAdmin = false;
        
        if ($currentUser) {
            if ($currentUser instanceof \App\Security\User\AdminUser || (method_exists($currentUser, 'getRoles') && in_array('ROLE_ADMIN', $currentUser->getRoles()))) {
                $isAdmin = true;
            }
        } else {
            // Manual Token Verification for Public Route
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                try {
                    $token = $matches[1];
                    $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
                    $claims = $verifiedIdToken->claims();
                    $email = (string) $claims->get('email');
                    
                    if (($claims->has('admin') && $claims->get('admin') === true) || 
                        ($claims->has('rol') && $claims->get('rol') === 'admin') ||
                         $email === 'admin@admin.com' || $email === 'adminTest5666@gmail.com') { // Whitelist check unificado
                        $isAdmin = true;
                    }
                } catch (\Throwable $e) {
                    // Ignore token errors
                }
            }
        }

        // --- REGISTRO (Vía Service) ---
        try {
            $this->organizationService->registerOrganization($dto, $isAdmin);
        } catch (\Throwable $e) {
            file_put_contents('last_error_org.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
            
            if (str_contains($e->getMessage(), 'ya está registrado')) {
                return $this->json(['error' => $e->getMessage()], 409);
            }
            return $this->json(['error' => 'Error al guardar organización', 'mensaje_tecnico' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Organización creada correctamente'], 201);
    }

    // =========================================================================
    // 3. LOGIN UNIFICADO (ELIMINADO - Usar Firebase)
    // =========================================================================
    //
    // =========================================================================
    // 4. CAMBIO DE CONTRASEÑA (ELIMINADO - Usar Firebase)
    // =========================================================================
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
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Usuario no autenticado o token inválido'], 401);
            }

            // --- 0. Caso Admin (Virtual) ---
            if ($user instanceof \App\Security\User\AdminUser) {
                 return $this->json([
                    'tipo' => 'admin',
                    'datos' => [
                        'dni' => 'ADMIN01',
                        'nombre' => 'Administrador Sistema',
                        'correo' => $user->getUserIdentifier(),
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
            file_put_contents('debug_profile_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
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
        $user = $this->getUser();
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
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar perfil', 'detalle' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Perfil actualizado correctamente']);
    }
}