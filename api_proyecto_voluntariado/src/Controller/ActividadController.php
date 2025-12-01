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

        // 4. INSERT MANUAL (Raw SQL)
        try {
            $conn = $em->getConnection();
            
            $sql = "
                INSERT INTO ACTIVIDADES (
                    NOMBRE, ESTADO, DIRECCION, MAX_PARTICIPANTES, 
                    FECHA_INICIO, FECHA_FIN, CIF_EMPRESA
                ) VALUES (
                    :nombre, :estado, :direccion, :max, 
                    :inicio, :fin, :cif
                )
            ";
            
            $params = [
                'nombre' => $dto->nombre,
                'estado' => $estado,
                'direccion' => $direccion,
                'max' => $maxParticipantes,
                'inicio' => $fechaInicioSql, // Enviamos string '20251201'
                'fin' => $fechaFinSql,       // Enviamos string '20251231'
                'cif' => $organizacion->getCif()
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
        return $this->json($actividades);
    }

    // INSCRIBIR VOLUNTARIO (Mantenemos Doctrine aquí, suele dar menos guerra porque no hay fechas)
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

        $actividad->addVoluntario($voluntario);

        try {
            $em->persist($actividad);
            $em->flush();
        } catch (\Exception $e) {
             return $this->json([
                'error' => 'Error al inscribir voluntario',
                'mensaje_tecnico' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'Inscripción realizada con éxito'], 200);
    }
}