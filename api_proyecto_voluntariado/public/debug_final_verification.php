<?php

use App\Kernel;
use App\Entity\Actividad;
use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists(Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv();
    $dotenv->loadEnv(__DIR__ . '/../.env');
}

$kernel = new Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();

// Fetch the specific activity (ID 33 from previous logs)
$actividad = $em->getRepository(Actividad::class)->find(33);

if (!$actividad) {
    echo "Activity 33 not found. Trying to find ANY activity...\n";
    $actividad = $em->getRepository(Actividad::class)->findOneBy([]);
}

if ($actividad) {
    echo "Found Activity ID: " . $actividad->getCodActividad() . "\n";
    echo "Direct Entity Description: " . $actividad->getDescripcion() . "\n";
    
    // Mimic the Controller Logic (ActividadController::list / getMisActividades)
    $data = [
        'codActividad' => $actividad->getCodActividad(),
        'nombre' => $actividad->getNombre(),
        'descripcion' => $actividad->getDescripcion(), // The field in question
        'estado' => $actividad->getEstado(),
        'estadoAprobacion' => $actividad->getEstadoAprobacion(),
        // Add other key fields to be sure
        'nombre_organizacion' => $actividad->getOrganizacion() ? $actividad->getOrganizacion()->getNombre() : 'N/A'
    ];
    
    echo "\n--- CONTROLLER RESPONSE SIMULATION ---\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "\n--------------------------------------\n";
    
    if (array_key_exists('descripcion', $data)) {
        echo "\nRESULT: 'descripcion' key IS present in the array.\n";
    } else {
        echo "\nRESULT: 'descripcion' key IS MISSING.\n";
    }

} else {
    echo "No activities found in DB.\n";
}
