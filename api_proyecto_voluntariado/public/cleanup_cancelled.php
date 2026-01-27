<?php
// public/cleanup_cancelled.php

$dbHost = '127.0.0.1';
$dbName = 'PROYECTOINTER';
$dbUser = 'sa';
$dbPass = '123456';

try {
    $conn = new PDO("sqlsrv:Server=$dbHost;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get IDs of CANCELADO activities
    $stmt = $conn->query("SELECT CODACTIVIDAD FROM ACTIVIDADES WHERE ESTADO = 'CANCELADO' OR ESTADO = 'Cancelado'");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ids)) {
        echo "No 'CANCELADO' activities found.\n";
        exit;
    }

    echo "Found " . count($ids) . " activities to delete. IDs: " . implode(", ", $ids) . "\n";
    $idList = implode(',', $ids);

    // 2. Delete Inscriptions for these activities
    $sqlInsc = "DELETE FROM INSCRIPCIONES WHERE CODACTIVIDAD IN ($idList)";
    $countInsc = $conn->exec($sqlInsc);
    echo "Deleted $countInsc related inscriptions.\n";

    // 3. Delete Activities
    $sqlAct = "DELETE FROM ACTIVIDADES WHERE CODACTIVIDAD IN ($idList)";
    $countAct = $conn->exec($sqlAct);
    echo "Deleted $countAct activities.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
