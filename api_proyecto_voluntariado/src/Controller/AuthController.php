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
    // 1. REGISTRO DE VOLUNTARIOS
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

        // 1. Deserializar JSON a DTO
        try {
            /** @var RegistroVoluntarioDTO $dto */
            $dto = $serializer->deserialize($json, RegistroVoluntarioDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'Datos JSON inválidos'], 400);
        }

        $repo = $entityManager->getRepository(Voluntario::class);

        // 2. Comprobar duplicados
        if ($repo->findOneBy(['dni' => $dto->dni])) {
            return $this->json(['error' => 'El DNI ya existe'], 409);
        }
        if ($repo->findOneBy(['correo' => $dto->correo])) {
            return $this->json(['error' => 'El correo ya existe'], 409);
        }

        // 3. Mapear DTO a Entidad Voluntario
        $voluntario = new Voluntario();
        $voluntario->setDni($dto->dni);
        $voluntario->setCorreo($dto->correo);
        $voluntario->setZona($dto->zona);
        $voluntario->setCursoCiclos($dto->ciclo);
        $voluntario->setExperiencia($dto->experiencia);

        // Lógica: Dividir "Nombre Completo" en Nombre y Apellidos
        $partes = explode(' ', trim($dto->nombreCompleto));
        $voluntario->setNombre($partes[0] ?? '');
        $voluntario->setApellido1($partes[1] ?? '');
        if (count($partes) > 2) {
            $voluntario->setApellido2(implode(' ', array_slice($partes, 2)));
        }

        // Lógica: Fecha de Nacimiento
        if ($dto->fechaNacimiento) {
            try {
                $voluntario->setFechaNacimiento(new \DateTime($dto->fechaNacimiento));
            } catch (\Exception $e) {}
        }

        // Lógica: Coche (Convertir string/bool a booleano)
        $cocheStr = strtolower((string)$dto->coche);
        $voluntario->setCoche(in_array($cocheStr, ['si', 'yes', 'true', '1']));

        // Lógica: Arrays a String
        $voluntario->setIdiomas(implode(', ', $dto->idiomas));
        $voluntario->setHabilidades(implode(', ', $dto->habilidades));
        $voluntario->setIntereses(implode(', ', $dto->intereses));

        // 4. Encriptar contraseña y Guardar
        $hashedPassword = $passwordHasher->hashPassword($voluntario, $dto->password);
        $voluntario->setPassword($hashedPassword);

        $entityManager->persist($voluntario);
        $entityManager->flush();

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

        // 1. Validar duplicados
        if ($repo->findOneBy(['cif' => $dto->cif])) {
            return $this->json(['error' => 'CIF ya registrado'], 409);
        }
        if ($repo->findOneBy(['email' => $dto->email])) {
            return $this->json(['error' => 'Email ya registrado'], 409);
        }

        // 2. Crear Entidad Organizacion
        $org = new Organizacion();
        $org->setCif($dto->cif);
        $org->setNombre($dto->nombre);
        $org->setEmail($dto->email);
        $org->setSector($dto->sector ?? '');
        $org->setDescripcion($dto->descripcion ?? '');
        $org->setLocalidad($dto->zona); // Mapeamos 'zona' del JSON a 'localidad' de la Entidad

        // 3. Encriptar contraseña y Guardar
        $hashedPassword = $passwordHasher->hashPassword($org, $dto->password);
        $org->setPassword($hashedPassword);

        $entityManager->persist($org);
        $entityManager->flush();

        return $this->json(['message' => 'Organización creada correctamente'], 201);
    }

    // =========================================================================
    // 3. LOGIN UNIFICADO
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

        // C. Verificar usuario y contraseña
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Credenciales inválidas'], 401);
        }

        // D. Éxito
        return $this->json([
            'message' => 'Login correcto',
            'id' => $user->getId(),
            'tipo' => $tipoUsuario,
            'nombre' => $user->getNombre(), // Asegúrate de que ambas entidades tengan este getter
        ]);
    }
}