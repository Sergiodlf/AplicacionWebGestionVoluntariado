<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$params = $container->get('doctrine.dbal.default_connection')->getParams();

echo "--- Doctrine Connection Params ---" . PHP_EOL;
echo "Driver: " . ($params['driver'] ?? 'N/A') . PHP_EOL;
echo "Host: " . ($params['host'] ?? 'N/A') . PHP_EOL;
echo "Port: " . ($params['port'] ?? 'N/A') . PHP_EOL;
echo "Dbname: " . ($params['dbname'] ?? 'N/A') . PHP_EOL;
echo "User: " . ($params['user'] ?? 'N/A') . PHP_EOL;
echo "----------------------------------" . PHP_EOL;
