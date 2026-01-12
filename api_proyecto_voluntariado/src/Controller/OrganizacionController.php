<?php

namespace App\Controller;

use App\Entity\Organizacion;
use App\Repository\OrganizacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 
use Symfony\Component\Validator\Validator\ValidatorInterface; 

/**
 * Controlador REST para la gestión de la entidad Organizacion.
 */
class OrganizacionController extends AbstractController
{

    /**
     * Obtiene la lista completa de organizaciones (GET /api/organizations).
     *
     * @param OrganizacionRepository $organizacionRepository Repositorio para buscar datos.
     * @return JsonResponse La lista de organizaciones serializada.
     */
    #[Route('/api/organizations', name: 'api_organizations_list', methods: ['GET'])]
    public function getOrganizations(OrganizacionRepository $organizacionRepository): JsonResponse
    {
        // 1. Obtiene todas las organizaciones de la base de datos.
        $organizaciones = $organizacionRepository->findAll();

        // 2. Serializa el array de objetos a JSON y lo devuelve.
        // Se usa 'org:read' para enviar solo los campos públicos (excluyendo el password).
        return $this->json(
            $organizaciones, 
            Response::HTTP_OK, 
            [], 
            ['groups' => ['org:read']]
        );
    }

    /**
     * Registra una nueva organización (POST /api/organizations).
     * * @param Request $request La petición HTTP (contiene el JSON con los datos).
     * @param EntityManagerInterface $entityManager Gestor de entidades.
     * @param SerializerInterface $serializer Serializador de JSON.
     * @param UserPasswordHasherInterface $passwordHasher Servicio para hashear contraseñas.
     * @param ValidatorInterface $validator Servicio para validar la entidad.
     * @return JsonResponse La organización creada o un error de validación/datos.
     */
    #[Route('/api/organizations', name: 'api_organizations_create', methods: ['POST'])]
    public function createOrganization(
        Request $request, 
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        // 1. Deserializar el JSON del cuerpo de la petición a un objeto Organizacion.
        try {
            /** @var Organizacion $organizacion */
            // Usamos 'org:write' para asegurar que el Serializador acepte el campo 'password'.
            $organizacion = $serializer->deserialize(
                $request->getContent(), 
                Organizacion::class, 
                'json',
                ['groups' => ['org:write']] 
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        // --- VALIDACIÓN DE UNICIDAD (PV-35) ---
        $repo = $entityManager->getRepository(Organizacion::class);
        
        // 1. Verificar CIF
        if ($organizacion->getCif() && $repo->find($organizacion->getCif())) {
            return $this->json(['error' => 'El CIF ya está registrado.'], Response::HTTP_CONFLICT);
        }

        // 2. Verificar Email
        if ($organizacion->getEmail() && $repo->findOneBy(['email' => $organizacion->getEmail()])) {
            return $this->json(['error' => 'El email ya está registrado.'], Response::HTTP_CONFLICT);
        }

        // 2. HASH DE CONTRASEÑA: Codifica la contraseña antes de guardar.
        $hashedPassword = $passwordHasher->hashPassword(
            $organizacion,
            $organizacion->getPassword() 
        );
        $organizacion->setPassword($hashedPassword);

        // 3. VALIDACIÓN: Verifica si el objeto cumple con las reglas de Doctrine/Symfony.
        $errors = $validator->validate($organizacion);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // 4. PERSISTENCIA: Guarda la entidad.
        $entityManager->persist($organizacion);
        $entityManager->flush();

        // 5. RESPUESTA: Devuelve la organización creada. 
        // Usamos 'org:read' para excluir el password de la respuesta enviada a Angular.
        return $this->json(
            $organizacion, 
            Response::HTTP_CREATED, 
            [], 
            ['groups' => ['org:read']]
        );
    }

    /**
     * Elimina una organización por su CIF (DELETE /api/organizations/{cif}).
     * * @param string $cif El CIF de la organización a eliminar.
     * @param OrganizacionRepository $organizacionRepository Repositorio.
     * @param EntityManagerInterface $entityManager Gestor de entidades.
     * @return JsonResponse Una respuesta con el código 204 si tiene éxito.
     */
    #[Route('/api/organizations/{cif}', name: 'api_organizations_delete', methods: ['DELETE'])]
    public function deleteOrganization(string $cif, OrganizacionRepository $organizacionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $organizacion = $organizacionRepository->find($cif);

        if (!$organizacion) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        // 1. Elimina la entidad.
        $entityManager->remove($organizacion);
        $entityManager->flush();

        // 2. Devuelve 204 No Content, que indica éxito sin necesidad de cuerpo de respuesta.
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Actualiza el estado de una organización (PATCH /api/organizations/{cif}/state).
     *
     * @param string $cif
     * @param Request $request
     * @param OrganizacionRepository $organizacionRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/api/organizations/{cif}/state', name: 'api_organizations_update_state', methods: ['PATCH'])]
    public function updateState(
        string $cif, 
        Request $request, 
        OrganizacionRepository $organizacionRepository, 
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $organizacion = $organizacionRepository->find($cif);
        if (!$organizacion) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;
        $estadosValidos = ['pendiente', 'aprobado', 'rechazado'];

        if (!$nuevoEstado || !in_array($nuevoEstado, $estadosValidos)) {
            return $this->json(
                ['message' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos)], 
                Response::HTTP_BAD_REQUEST
            );
        }

        $organizacion->setEstado($nuevoEstado);
        $entityManager->flush();

        return $this->json($organizacion, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }

    /**
     * Obtiene una organización por su email (POST /api/organizations/by-email).
     *
     * @param Request $request
     * @param OrganizacionRepository $organizacionRepository
     * @return JsonResponse
     */
    #[Route('/api/organizations/by-email', name: 'api_organizations_get_by_email', methods: ['POST'])]
    public function getByEmail(Request $request, OrganizacionRepository $organizacionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'El campo email es obligatorio.'], Response::HTTP_BAD_REQUEST);
        }

        $organizacion = $organizacionRepository->findOneBy(['email' => $email]);

        if (!$organizacion) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($organizacion, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }

    /**
     * Actualiza los datos de una organización (PUT /api/organizations/{cif}).
     *
     * @param string $cif
     * @param Request $request
     * @param OrganizacionRepository $organizacionRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/api/organizations/{cif}', name: 'api_organizations_update', methods: ['PUT'])]
    public function update(
        string $cif, 
        Request $request, 
        OrganizacionRepository $organizacionRepository, 
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $organizacion = $organizacionRepository->find($cif);
        if (!$organizacion) {
            return $this->json(['message' => 'Organización no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Campos permitidos para actualización
        if (isset($data['nombre'])) $organizacion->setNombre($data['nombre']);
        if (isset($data['email'])) $organizacion->setEmail($data['email']);
        if (isset($data['sector'])) $organizacion->setSector($data['sector']);
        if (isset($data['direccion'])) $organizacion->setDireccion($data['direccion']);
        if (isset($data['localidad'])) $organizacion->setLocalidad($data['localidad']);
        if (isset($data['cp'])) $organizacion->setCp($data['cp']);
        if (isset($data['descripcion'])) $organizacion->setDescripcion($data['descripcion']);
        if (isset($data['contacto'])) $organizacion->setContacto($data['contacto']);

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar la organización: ' . $e->getMessage()], 500);
        }

        return $this->json($organizacion, Response::HTTP_OK, [], ['groups' => ['org:read']]);
    }
}