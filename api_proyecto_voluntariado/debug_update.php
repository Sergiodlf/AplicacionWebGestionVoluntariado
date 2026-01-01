<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$conn = $em->getConnection();

echo "Checking Column Info:\n";
$sqlInfo = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ACTIVIDADES' AND COLUMN_NAME = 'ESTADO'";
$stmt = $conn->executeQuery($sqlInfo);
echo "\nChecking Specific Constraint Definition:\n";
try {
    $sql = "SELECT definition FROM sys.check_constraints WHERE name = 'CK_ACTIVIDADES_ESTADO'";
    $stmt = $conn->executeQuery($sql);
    $result = $stmt->fetchOne();
    echo "Constraint Definition: " . $result . "\n";

    echo "\nDropping Constraint...\n";
    $conn->executeStatement("ALTER TABLE ACTIVIDADES DROP CONSTRAINT CK_ACTIVIDADES_ESTADO");
    echo "Constraint DROPPED.\n";

    echo "\nRetrying UPDATE...\n";
    $conn->executeStatement("UPDATE ACTIVIDADES SET ESTADO = 'Cancelado' WHERE CODACTIVIDAD = 1");
    echo "Update SUCCESS (after drop)!\n";
} catch (\Exception $e) { echo "Error: " . $e->getMessage(); }
