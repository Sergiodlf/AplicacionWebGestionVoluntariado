<?php

namespace App\Controller;

use App\Entity\Inscripcion;
use App\Entity\Voluntario;
use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Repository\InscripcionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/inscripciones', name: 'api_inscripciones_')]
class InscripcionController extends AbstractController
{
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $estado = $request->query->get('estado');
            $repo = $em->getRepository(Inscripcion::class);

            if ($estado) {
                // Support multiple statuses separated by comma (e.g., "PENDIENTE,CONFIRMADO")
                $estados = array_map('strtoupper', array_map('trim', explode(',', $estado)));
                $inscripciones = $repo->findBy(['estado' => $estados]);
            } else {
                // Default: get all
                $inscripciones = $repo->findAll();
            }

            $data = [];
            foreach ($inscripciones as $inscripcion) {
                $voluntario = $inscripcion->getVoluntario();
                $actividad = $inscripcion->getActividad();

                if (!$voluntario || !$actividad) {
                    continue; // Skip corrupted records
                }

                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'dni_voluntario' => $voluntario->getDni(),
                    'nombre_voluntario' => $voluntario->getNombre() . ' ' . $voluntario->getApellido1(),
                    'email_voluntario' => $voluntario->getCorreo(),
                    'habilidades_voluntario' => $voluntario->getHabilidades(),
                    'disponibilidad_voluntario' => $voluntario->getDisponibilidad(),
                    'intereses_voluntario' => $voluntario->getIntereses(),
                    
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre_actividad' => $actividad->getNombre(),
                    'descripcion_actividad' => '', // $actividad->getDescripcion(),
                    'email_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getEmail() : '',
                    'horario' => $actividad->getHorario(),
                    'habilidades_actividad' => $actividad->getHabilidades(), // Requisitos
                    
                    'estado' => $inscripcion->getEstado()
                ];
            }

            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Error interno en el servidor',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dni = $data['dniVoluntario'] ?? null;
        $codActividad = $data['codActividad'] ?? null;

        if (!$dni || !$codActividad) {
            return $this->json(['error' => 'Faltan datos (dniVoluntario, codActividad)'], 400);
        }

        $voluntario = $em->getRepository(Voluntario::class)->find($dni);
        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $actividad = $em->getRepository(Actividad::class)->find($codActividad);
        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        // Verificar si ya existe la inscripción
        $existing = $em->getRepository(Inscripcion::class)->findOneBy([
            'voluntario' => $voluntario,
            'actividad' => $actividad
        ]);

        if ($existing) {
            return $this->json(['error' => 'El voluntario ya está inscrito en esta actividad'], 409);
        }

        $inscripcion = new Inscripcion();
        $inscripcion->setVoluntario($voluntario);
        $inscripcion->setActividad($actividad);
        $inscripcion->setEstado('PENDIENTE');

        $em->persist($inscripcion);
        $em->flush();

        return $this->json(['message' => 'Inscripción creada correctamente'], 201);
    }

    #[Route('/{id}/estado', name: 'update_estado', methods: ['PATCH'])]
    public function updateEstado(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $inscripcion = $em->getRepository(Inscripcion::class)->find($id);

        if (!$inscripcion) {
            return $this->json(['error' => 'Inscripción no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $nuevoEstado = $data['estado'] ?? null;

        if (!$nuevoEstado) {
            return $this->json(['error' => 'Falta el campo "estado"'], 400);
        }

        // Validación de estados permitidos
        $estadosPermitidos = ['PENDIENTE', 'CONFIRMADO', 'RECHAZADO', 'EN_CURSO', 'FINALIZADO', 'COMPLETADA'];
        if (!in_array(strtoupper($nuevoEstado), $estadosPermitidos)) {
            return $this->json([
                'error' => 'Estado inválido',
                'permitidos' => $estadosPermitidos
            ], 400);
        }

        $inscripcion->setEstado(strtoupper($nuevoEstado));

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al actualizar el estado'], 500);
        }

        return $this->json([
            'message' => 'Estado actualizado correctamente',
            'nuevo_estado' => $inscripcion->getEstado()
        ]);
    }

    #[Route('/voluntario/{dni}/pendientes', name: 'get_pending_by_voluntario', methods: ['GET'])]
    public function getPendingByVoluntario(string $dni, EntityManagerInterface $em): JsonResponse
    {
        $voluntario = $em->getRepository(Voluntario::class)->find($dni);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        $inscripciones = $em->getRepository(Inscripcion::class)->findBy([
            'voluntario' => $voluntario,
            'estado' => 'PENDIENTE'
        ]);

        $data = [];
        foreach ($inscripciones as $inscripcion) {
            $data[] = [
                'id_inscripcion' => $inscripcion->getId(),
                'codActividad' => $inscripcion->getActividad()->getCodActividad(),
                'nombre_actividad' => $inscripcion->getActividad()->getNombre(),
                'fecha_actividad' => $inscripcion->getActividad()->getFechaInicio()->format('Y-m-d H:i'),
                'nombre_organizacion' => $inscripcion->getActividad()->getOrganizacion() ? $inscripcion->getActividad()->getOrganizacion()->getNombre() : 'Desconocida',
                'estado' => $inscripcion->getEstado()
            ];
        }

        return $this->json($data);
    }

    #[Route('/voluntario/{dni}/inscripciones', name: 'get_inscripciones_voluntario', methods: ['GET'])]
    public function getInscripcionesVoluntario(string $dni, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $voluntario = $em->getRepository(Voluntario::class)->find($dni);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        // Recuperar parámetro 'estado' (puede ser 'PENDIENTE', 'CONFIRMADO', 'EN_CURSO', 'FINALIZADO', etc.)
        $estadoInscripcion = $request->query->get('estado'); 

        $criteria = ['voluntario' => $voluntario];
        if ($estadoInscripcion) {
            $criteria['estado'] = strtoupper($estadoInscripcion);
        }

        $inscripciones = $em->getRepository(Inscripcion::class)->findBy($criteria, ['id' => 'DESC']);

        $data = [];
        foreach ($inscripciones as $inscripcion) {
            $actividad = $inscripcion->getActividad();
            
            if ($actividad) {
                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'estado_inscripcion' => $inscripcion->getEstado(),
                    
                    // Datos Actividad
                    'codActividad' => $actividad->getCodActividad(),
                    'nombre' => $actividad->getNombre(),
                    'direccion' => $actividad->getDireccion(),
                    'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i') : null,
                    'fechaFin' => $actividad->getFechaFin() ? $actividad->getFechaFin()->format('Y-m-d H:i') : null,
                    'organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getNombre() : 'Desconocida',
                    'estado_actividad' => $actividad->getEstado(),
                    'ods' => $actividad->getOds(),
                    'habilidades' => $actividad->getHabilidades(),
                ];
            }
        }

        return $this->json($data);
    }

    #[Route('/organizacion/{cif}', name: 'get_inscripciones_by_organizacion', methods: ['GET'])]
    public function getInscripcionesByOrganizacion(string $cif, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            // 1. Check Organizacion
            $organizacion = $em->getRepository(Organizacion::class)->find($cif);
            if (!$organizacion) {
                return $this->json(['error' => 'Organización no encontrada'], 404);
            }
            
            // 2. Query Repository
            /** @var InscripcionRepository $inscripcionRepository */
            $inscripcionRepository = $em->getRepository(Inscripcion::class);
            $estado = $request->query->get('estado');
            
            $inscripciones = $inscripcionRepository->findByOrganizacionAndEstado($cif, $estado);
            
            $data = [];
            foreach ($inscripciones as $inscripcion) {
                $voluntario = $inscripcion->getVoluntario();
                $actividad = $inscripcion->getActividad();
                
                if (!$voluntario || !$actividad) {
                    continue;
                }
    
                $telefono = null;
                if (method_exists($voluntario, 'getTelefono')) {
                    $telefono = $voluntario->getTelefono();
                }

                $data[] = [
                    'id_inscripcion' => $inscripcion->getId(),
                    'estado' => $inscripcion->getEstado(),
                    // 'fecha_inscripcion' => $inscripcion->getFechaInscripcion() ? $inscripcion->getFechaInscripcion()->format('Y-m-d H:i') : null,
                    'voluntario' => [
                        'dni' => $voluntario->getDni(),
                        // 'nombre' => $voluntario->getNombre() . ' ' . $voluntario->getApellido1(),
                        // 'email' => $voluntario->getUserIdentifier(), 
                        'telefono' => $telefono
                    ],
                    'actividad' => [
                        'codActividad' => $actividad->getCodActividad(),
                        // 'nombre' => $actividad->getNombre()
                    ]
                ];
            }
            
            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
}
