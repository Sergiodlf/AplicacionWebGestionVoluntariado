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

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
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
        if (!$dto->dni || !$dto->email || !$dto->nombre || !$dto->password) {
            return $this->json(['error' => 'Faltan campos obligatorios (dni, email, nombre, password)'], 400);
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
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al registrar voluntario', 'detalle' => $e->getMessage()], 500);
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
        if ($email === 'admin@admin.com' && $password === 'admin123') {
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
}