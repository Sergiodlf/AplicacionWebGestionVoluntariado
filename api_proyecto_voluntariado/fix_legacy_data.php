<?php

use App\Kernel;
use App\Entity\Actividad;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$activities = $em->getRepository(Actividad::class)->findAll();
$count = 0;

foreach ($activities as $a) {
    echo "Processing ID: " . $a->getCodActividad() . "\n";
    $dirty = false;

    // Fix Aprobacion
    if ($a->getEstadoAprobacion() === 'ACEPTADO') {
        $a->setEstadoAprobacion('ACEPTADA');
        echo "  - Fixed ACEPTADO -> ACEPTADA\n";
        $dirty = true;
    }

    // Fix Estado (Legacy ABIERTA -> En curso or Sin comenzar)
    if ($a->getEstado() === 'ABIERTA') {
        $now = new \DateTime();
        if ($a->getFechaInicio() > $now) {
            $newState = 'Sin comenzar';
        } else {
            $newState = 'En curso';
        }
        $a->setEstado($newState);
        echo "  - Fixed ABIERTA -> $newState\n";
        $dirty = true;
    }

    if ($dirty) {
        $count++;
    }
}

if ($count > 0) {
    $em->flush();
    echo "Updated $count activities.\n";
} else {
    echo "No activities needed updates.\n";
}
