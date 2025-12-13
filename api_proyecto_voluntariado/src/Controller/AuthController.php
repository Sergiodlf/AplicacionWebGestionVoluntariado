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

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
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

        $repo = $entityManager->getRepository(Voluntario::class);
        if ($repo->findOneBy(['dni' => $dto->dni])) {
            return $this->json(['error' => 'El DNI ya existe'], 409);
        }
        if ($repo->findOneBy(['correo' => $dto->email])) {
            return $this->json(['error' => 'El correo ya existe'], 409);
        }

        // --- PREPARACIÓN DE DATOS ---

        // 1. Nombres
        $partes = explode(' ', trim($dto->nombre));
        $nombre = $partes[0] ?? '';
        $apellido1 = $partes[1] ?? '';
        $apellido2 = (count($partes) > 2) ? implode(' ', array_slice($partes, 2)) : '';

        // 2. Fecha (Formato Ymd para SQL Server)
        $fechaSql = null;
        if ($dto->fechaNacimiento) {
            try {
                $fechaObj = new \DateTime($dto->fechaNacimiento);
                $fechaSql = $fechaObj->format('Ymd'); 
            } catch (\Exception $e) {}
        }

        // 3. Coche
        $cocheStr = strtolower((string)$dto->coche);
        $cocheBit = in_array($cocheStr, ['si', 'yes', 'true', '1']) ? 1 : 0;

        // 4. Arrays
        $idiomas = implode(', ', $dto->idiomas);
        $habilidades = implode(', ', $dto->habilidades);
        $intereses = implode(', ', $dto->intereses);

        // 5. Contraseña (CORRECCIÓN IMPORTANTE)
        // Usamos un objeto Voluntario "lleno" para generar el hash.
        // Esto asegura que si el hasher usa el email/dni como salt, coincida con el login.
        $userParaHash = new Voluntario();
        $userParaHash->setCorreo($dto->email); // Clave para el UserIdentifier
        $userParaHash->setDni($dto->dni);
        
        $hashedPassword = $passwordHasher->hashPassword($userParaHash, $dto->password);

        // --- INSERT MANUAL ---
        try {
            $conn = $entityManager->getConnection();
            
            $sql = "
                INSERT INTO VOLUNTARIOS (
                    DNI, NOMBRE, APELLIDO1, APELLIDO2, CORREO, PASSWORD, 
                    COCHE, FECHA_NACIMIENTO, ZONA, EXPERIENCIA, 
                    IDIOMAS, HABILIDADES, INTERESES
                ) VALUES (
                    :dni, :nombre, :ap1, :ap2, :correo, :pass, 
                    :coche, :fecha, :zona, :exp, 
                    :idiomas, :hab, :int
                )
            ";

            $params = [
                'dni' => $dto->dni,
                'nombre' => $nombre,
                'ap1' => $apellido1,
                'ap2' => $apellido2,
                'correo' => $dto->email,
                'pass' => $hashedPassword,
                'coche' => $cocheBit,
                'fecha' => $fechaSql,
                'zona' => $dto->zona,
                'exp' => $dto->experiencia,
                'idiomas' => $idiomas,
                'hab' => $habilidades,
                'int' => $intereses
            ];

            $conn->executeStatement($sql, $params);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error SQL al guardar', 'detalle' => $e->getMessage()], 500);
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