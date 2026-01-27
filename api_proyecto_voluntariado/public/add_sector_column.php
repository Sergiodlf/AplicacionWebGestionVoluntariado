<?php
// public/add_sector_column.php

$dbHost = '127.0.0.1';
$dbName = 'PROYECTOINTER';
$dbUser = 'sa';
$dbPass = '123456';

try {
    $conn = new PDO("sqlsrv:Server=$dbHost;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "ALTER TABLE ACTIVIDADES ADD SECTOR NVARCHAR(50)";
    $conn->exec($sql);
    
    echo "SUCCESS: Column SECTOR added.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Column already exists') !== false || strpos($e->getMessage(), 'ya existe') !== false) {
        echo "SUCCESS: Column SECTOR already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
