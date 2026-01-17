<?php
$serverName = "127.0.0.1,1433"; 
// No UID/PWD implies Windows Authentication
$connectionOptions = array(
    "Database" => "PROYECTOINTER",
    "TrustServerCertificate" => true
);

echo "Attempting to connect to SQL Server using Windows Auth...\n";

//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if($conn) {
    echo "Windows Auth SUCCESS!\n";
    sqlsrv_close($conn);
    exit(0);
} else {
    echo "Windows Auth FAILED.\n";
    die(print_r(sqlsrv_errors(), true));
}
