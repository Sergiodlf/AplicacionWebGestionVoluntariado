<?php

namespace App\Controller;

use App\Entity\Actividad;
use App\Entity\Organizacion;
use App\Entity\Voluntario;
use App\Model\CrearActividadDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/actividades', name: 'api_actividades_')]
class ActividadController extends AbstractController
{
    // =========================================================================
    // CREAR ACTIVIDAD (SOLUCIÓN BLINDADA: SQL PURO)
    // =========================================================================
    #[Route('/crear', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $json = $request->getContent();

        try {
            /** @var CrearActividadDTO $dto */
            $dto = $serializer->deserialize($json, CrearActividadDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        // 1. Validar Organización
        $organizacion = $em->getRepository(Organizacion::class)->find($dto->cifOrganizacion);
        if (!$organizacion) {
            return $this->json(['error' => 'Organización no encontrada. Revisa el CIF.'], 404);
        }

        // 2. Preparar Fechas (FORMATO Ymd PARA SQL SERVER)
        // Esto es lo que evita el error SQLSTATE [22007, 241]
        $fechaInicioSql = null;
        $fechaFinSql = null;

        try {
            // Fecha Inicio
            if ($dto->fechaInicio) {
                $fInicio = new \DateTime($dto->fechaInicio);
                $fechaInicioSql = $fInicio->format('Ymd'); // Ej: 20251201
            } else {
                $fInicio = new \DateTime();
                $fechaInicioSql = $fInicio->format('Ymd');
            }

            // Fecha Fin
            if (!empty($dto->fechaFin)) {
                $fFin = new \DateTime($dto->fechaFin);
                $fechaFinSql = $fFin->format('Ymd');
            } else {
                // Por defecto +30 días
                $fFin = (new \DateTime())->modify('+30 days');
                $fechaFinSql = $fFin->format('Ymd');
            }

            // Validación extra de lógica (Fin > Inicio)
            if ($fFin < $fInicio) {
                return $this->json(['error' => 'La fecha de fin no puede ser anterior a la de inicio'], 400);
            }

        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato de fecha inválido. Usa AAAA-MM-DD'], 400);
        }

        // 3. Preparar otros datos
        $maxParticipantes = $dto->maxParticipantes ?? 10;
        $direccion = $dto->direccion ?? 'Sede Principal';
        $estado = 'En Curso';

        // ODS (Array -> JSON)
        $odsJson = null;
        if (!empty($dto->ods)) {
            $odsJson = json_encode($dto->ods);
        }

        // Habilidades (Array -> JSON)
        $habilidadesJson = '[]';
        if (!empty($dto->habilidades)) {
            $habilidadesJson = json_encode($dto->habilidades);
        }

        // 4. INSERT MANUAL (Raw SQL)
        try {
            $conn = $em->getConnection();
            
            $sql = "
                INSERT INTO ACTIVIDADES (
                    NOMBRE, ESTADO, DIRECCION, MAX_PARTICIPANTES, 
                    FECHA_INICIO, FECHA_FIN, CIF_EMPRESA, ODS, HABILIDADES
                ) VALUES (
                    :nombre, :estado, :direccion, :max, 
                    :inicio, :fin, :cif, :ods, :habilidades
                )
            ";
            
            $params = [
                'nombre' => $dto->nombre,
                'estado' => $estado,
                'direccion' => $direccion,
                'max' => $maxParticipantes,
                'inicio' => $fechaInicioSql, // Enviamos string '20251201'
                'fin' => $fechaFinSql,       // Enviamos string '20251231'
                'cif' => $organizacion->getCif(),
                'ods' => $odsJson,
                'habilidades' => $habilidadesJson
            ];

            $conn->executeStatement($sql, $params);
            
            // Recuperar el ID generado (IDENTITY) para devolverlo
            $nuevoId = $conn->fetchOne("SELECT @@IDENTITY");

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error crítico al guardar actividad en BD',
                'mensaje_tecnico' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'Actividad creada con éxito', 'id' => $nuevoId], 201);
    }
    
    // LISTAR TODAS
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $actividades = $em->getRepository(Actividad::class)->findAll();
        
        $data = [];
        foreach ($actividades as $actividad) {
            $data[] = [
                'codActividad' => $actividad->getCodActividad(),
                'nombre' => $actividad->getNombre(),
                'estado' => $actividad->getEstado(),
                'direccion' => $actividad->getDireccion(),
                'fechaInicio' => $actividad->getFechaInicio() ? $actividad->getFechaInicio()->format('Y-m-d H:i:s') : null,
                'fechaFin' => $actividad->getFechaFin() ? $actividad->getFechaFin()->format('Y-m-d H:i:s') : null,
                'maxParticipantes' => $actividad->getMaxParticipantes(),
                'ods' => $actividad->getOds(), // Devolvemos el string guardado
                'habilidades' => $actividad->getHabilidades(),
                // 'organizacion' => ... (podríamos poner el objeto entero, pero evitamos recursión)
                'nombre_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getNombre() : 'Organización Desconocida',
                'cif_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getCif() : null,
            ];
        }

        return $this->json($data);
    }

    // INSCRIBIR VOLUNTARIO (Actualizado para usar la entidad Inscripcion)
    #[Route('/{id}/inscribir', name: 'inscribir', methods: ['POST'])]
    public function inscribir(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $actividad = $em->getRepository(Actividad::class)->find($id);

        if (!$actividad) {
            return $this->json(['error' => 'Actividad no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $dniVoluntario = $data['dni'] ?? $data['dni_voluntario'] ?? null;

        if (!$dniVoluntario) {
            return $this->json(['error' => 'Falta el campo "dni"'], 400);
        }

        $voluntario = $em->getRepository(Voluntario::class)->find($dniVoluntario);

        if (!$voluntario) {
            return $this->json(['error' => 'Voluntario no encontrado'], 404);
        }

        // Comprobar si ya existe la inscripción
        $inscripcionRepo = $em->getRepository(\App\Entity\Inscripcion::class);
        $existe = $inscripcionRepo->findOneBy(['actividad' => $actividad, 'voluntario' => $voluntario]);

        if ($existe) {
            return $this->json(['error' => 'El voluntario ya está inscrito en esta actividad'], 409);
        }

        // Crear nueva inscripción
        $inscripcion = new \App\Entity\Inscripcion();
        $inscripcion->setActividad($actividad);
        $inscripcion->setVoluntario($voluntario);
        $inscripcion->setEstado('PENDIENTE');
        // $inscripcion->setFechaInscripcion(new \DateTime()); // Ya se pone en el constructor

        try {
            $em->persist($inscripcion);
            $em->flush();
        } catch (\Exception $e) {
             return $this->json([
                'error' => 'Error al inscribir voluntario',
                'mensaje_tecnico' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'Solicitud de inscripción enviada con estado PENDIENTE'], 201);
    }
}