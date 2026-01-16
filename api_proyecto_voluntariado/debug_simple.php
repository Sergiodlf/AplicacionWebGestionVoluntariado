<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Actividad;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$actividades = $em->getRepository(Actividad::class)->findAll();

echo "Total Actividades: " . count($actividades) . PHP_EOL;
foreach ($actividades as $a) {
    echo sprintf(
        "ID: %d | Nombre: %s | EstadoAprobacion: '%s' | EstadoNew: '%s' | Estado: '%s' | Org: %s" . PHP_EOL,
        $a->getCodActividad(),
        $a->getNombre(),
        $a->getEstadoAprobacion(),
        $a->getEstadoNew(),
        $a->getEstado(),
        $a->getOrganizacion() ? $a->getOrganizacion()->getCif() : 'NULL'
    );
}
