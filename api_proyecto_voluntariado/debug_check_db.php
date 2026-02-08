<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Actividad;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$activities = $em->getRepository(Actividad::class)->findAll();

echo "Total Activities: " . count($activities) . "\n";
foreach ($activities as $a) {
    echo "ID: " . $a->getCodActividad() . 
         " | Nombre: " . $a->getNombre() . 
         " | Aprobacion: " . $a->getEstadoAprobacion() . 
         " | Estado: " . $a->getEstado() . 
         " | Org: " . ($a->getOrganizacion() ? $a->getOrganizacion()->getCif() . ' (' . $a->getOrganizacion()->getNombre() . ')' : 'NULL') . 
         "\n";
}

$email = 'jrenzullis@gmail.com';
$org = $em->getRepository(\App\Entity\Organizacion::class)->findOneBy(['email' => $email]);
echo "\nChecking User Email: $email\n";
if ($org) {
    echo "Found Organization: CIF=" . $org->getCif() . " | Nombre=" . $org->getNombre() . "\n";
} else {
    echo "Organization NOT FOUND for this email.\n";
}

echo "\n--- All Organizations ---\n";
$orgs = $em->getRepository(\App\Entity\Organizacion::class)->findAll();
foreach ($orgs as $o) {
    echo "CIF: " . $o->getCif() . " | Nombre: " . $o->getNombre() . " | Email: '" . $o->getEmail() . "'\n";
}

