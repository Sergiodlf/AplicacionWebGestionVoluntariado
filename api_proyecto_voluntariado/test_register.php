<?php

function request($method, $url, $data = null) {
    $options = [
        "http" => [
            "header" => "Content-type: application/json\r\n",
            "method" => $method,
            "ignore_errors" => true
        ],
        "ssl" => [
             "verify_peer" => false, 
             "verify_peer_name" => false
        ]
    ];

    if ($data) {
        $options["http"]["content"] = json_encode($data);
    }

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    $responseCode = 0;
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/.* (\d+)/', $header, $matches)) {
            $responseCode = intval($matches[1]);
        }
    }

    return ['code' => $responseCode, 'body' => $result];
}

$url = "https://127.0.0.1:8000/api/auth/register/voluntario";
// Fallback HTTP
if (!@file_get_contents("https://127.0.0.1:8000", false, stream_context_create(["ssl"=>["verify_peer"=>false,"verify_peer_name"=>false]]))) {
    $url = "http://127.0.0.1:8000/api/auth/register/voluntario";
}

$dni = "TEST" . rand(1000, 9999);
$email = "test" . rand(1000, 9999) . "@example.com";

$data = [
    "dni" => $dni,
    "email" => $email,
    "nombre" => "Juan Test",
    "password" => "secret123",
    "zona" => "Norte",
    "ciclo" => "DAM", // String variant
    "fechaNacimiento" => "2000-01-01",
    "experiencia" => "Ninguna",
    "coche" => true,
    "idiomas" => ["Ingles"],
    "disponibilidad" => ["Lunes"]
];

echo "Enviando registro a $url...\n";
echo "Datos: " . json_encode($data) . "\n";

$response = request('POST', $url, $data);

echo "Code: " . $response['code'] . "\n";
echo "Body: " . $response['body'] . "\n";
