<?php
// public/debug_raw_db.php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;

require_once dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$orgs = $em->getRepository(\App\Entity\Organizacion::class)->findAll();

echo "\n--- RAW DB DUMP ---\n";
echo "Count: " . count($orgs) . "\n";
foreach ($orgs as $org) {
    echo "Org: " . $org->getNombre() . " | " . $org->getEmail() . " | " . $org->getEstado() . "\n";
}
echo "-------------------\n";
