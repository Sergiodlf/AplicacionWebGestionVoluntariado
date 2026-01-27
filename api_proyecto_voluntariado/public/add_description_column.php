<?php
// public/add_description_column.php

$dbHost = '127.0.0.1';
$dbName = 'PROYECTOINTER';
$dbUser = 'sa';
$dbPass = '123456';

try {
    $conn = new PDO("sqlsrv:Server=$dbHost;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Using NVARCHAR(MAX) to map Doctrine's Types::TEXT in SQL Server
    $sql = "ALTER TABLE ACTIVIDADES ADD DESCRIPCION NVARCHAR(MAX)";
    $conn->exec($sql);
    
    echo "SUCCESS: Column DESCRIPCION added.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Column already exists') !== false || strpos($e->getMessage(), 'ya existe') !== false) {
        echo "SUCCESS: Column DESCRIPCION already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
