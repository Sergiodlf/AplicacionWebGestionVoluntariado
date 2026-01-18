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

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private $volunteerService;
    private $firebaseAuth;

    public function __construct(VolunteerService $volunteerService, Auth $firebaseAuth)
    {
        $this->volunteerService = $volunteerService;
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

        // --- REGISTRO (Vía Service) ---
        try {
            $this->volunteerService->registerVolunteer($dto);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Error al registrar voluntario', 'detalle' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }

        return $this->json(['message' => 'Voluntario registrado correctamente'], 201);
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

        $repo = $entityManager->getRepository(Organizacion::class);

        if ($repo->find($dto->cif)) {
            return $this->json(['error' => 'CIF ya registrado'], 409);
        }
        if ($repo->findOneBy(['email' => $dto->email])) {
            return $this->json(['error' => 'Email ya registrado'], 409);
        }

        $org = new Organizacion();
        $org->setCif($dto->cif);
        $org->setNombre($dto->nombre);
        $org->setEmail($dto->email);
        $org->setDireccion($dto->direccion);
        $org->setCp($dto->cp);
        $org->setLocalidad($dto->localidad);
        $org->setDescripcion($dto->descripcion);
        $org->setContacto($dto->contacto);

        if ($dto->sector) $org->setSector($dto->sector);

        $hashedPassword = $passwordHasher->hashPassword($org, $dto->password);
        $org->setPassword($hashedPassword);

        try {
            $entityManager->getConnection()->executeStatement("SET DATEFORMAT ymd");
            $entityManager->persist($org);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al guardar organización', 'mensaje_tecnico' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Organización creada correctamente'], 201);
    }

    // =========================================================================
    // 3. LOGIN UNIFICADO (MEJORADO PARA DEBUG)
    // =========================================================================
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Faltan credenciales (email y password)'], 400);
        }

        // --- ACCESO PROVISIONAL ADMINISTRADOR (HARDCODED) ---
        if ($email === 'admin@admin.com' && $password === '1234567') {
            return $this->json([
                'message' => 'Login correcto (Admin)',
                'id' => 'ADMIN01',
                'tipo' => 'admin',
                'nombre' => 'Administrador Sistema'
            ]);
        }

        // A. Buscar en tabla Voluntarios
        $user = $entityManager->getRepository(Voluntario::class)->findOneBy(['correo' => $email]);
        $tipoUsuario = 'voluntario';

        // B. Si no está, buscar en tabla Organizaciones
        if (!$user) {
            $user = $entityManager->getRepository(Organizacion::class)->findOneBy(['email' => $email]);
            $tipoUsuario = 'organizacion';
        }

        // Si no encontramos al usuario en ninguna tabla
        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // C. Verificar contraseña
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Contraseña incorrecta'], 401);
        }

        return $this->json([
            'message' => 'Login correcto',
            'id' => ($tipoUsuario === 'voluntario') ? $user->getDni() : $user->getCif(),
            'tipo' => $tipoUsuario,
            'nombre' => $user->getNombre(),
        ]);
    }
    // =========================================================================
    // 4. CAMBIO DE CONTRASEÑA
    // =========================================================================
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        // 1. Obtener usuario del Token (Smart access)
        $user = $this->getUser();
        
        if (!$user) {
             return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $oldPassword = $data['oldPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$oldPassword || !$newPassword) {
            return $this->json(['error' => 'Faltan datos obligatorios (oldPassword, newPassword)'], 400);
        }

        // 2. Verificar contraseña actual
        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return $this->json(['error' => 'La contraseña actual es incorrecta'], 401);
        }

        // 3. Hashear y guardar nueva contraseña
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar la contraseña', 'detalle' => $e->getMessage()], 500);
        }

        return $this->json(['message' => 'Contraseña actualizada correctamente']);
    }
    // =========================================================================
    // 5. OBTENER PERFIL (UNIFICADO)
    // =========================================================================
    #[Route('/profile', name: 'profile', methods: ['POST'])]
    // =========================================================================
    // 5. OBTENER PERFIL (UNIFICADO)
    // =========================================================================
    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function getProfile(
        SerializerInterface $serializer
    ): JsonResponse
    {
        // El usuario ya viene autenticado por el Token Handler (UnifiedUserProvider)
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Usuario no autenticado o token inválido'], 401);
        }

        // --- 1. Caso Voluntario ---
        if ($user instanceof Voluntario) {
            // BACKDOOR: Si es el admin@admin.com, devolvemos tipo 'admin'
            if ($user->getCorreo() === 'admin@admin.com') {
                 return $this->json([
                    'tipo' => 'admin',
                    'datos' => [
                        'dni' => $user->getDni(),
                        'nombre' => 'Administrador',
                        'correo' => $user->getCorreo(),
                        'zona' => 'Global'
                    ]
                ]);
            }

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

        return $this->json(['error' => 'Tipo de usuario desconocido'], 500);
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
            if (isset($data['disponibilidad'])) $user->setDisponibilidad($data['disponibilidad']); // Array
            if (isset($data['idiomas'])) $user->setIdiomas($data['idiomas']); // Array

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