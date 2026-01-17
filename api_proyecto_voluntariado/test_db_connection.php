<?php
$serverName = "127.0.0.1,1433"; //serverName\instanceName, portNumber (default is 1433)
$connectionOptions = array(
    "Database" => "PROYECTOINTER",
    "Uid" => "sa",
    "PWD" => "1234",
    "TrustServerCertificate" => true
);

echo "Attempting to connect to SQL Server at $serverName...\n";

//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if($conn) {
    echo "Connection established.\n";
    sqlsrv_close($conn);
    exit(0);
} else {
    echo "Connection could not be established.\n";
    die(print_r(sqlsrv_errors(), true));
}
