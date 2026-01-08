<?php

$url = 'http://127.0.0.1:8888/api/auth/register/voluntario';

// Generar datos aleatorios para evitar duplicados
$rand = rand(1000, 9999);
$dni = $rand . '1234' . chr(rand(65, 90)); // Generar DNI válido (aprox)
$email = "testVal{$rand}@example.com";

$data = [
    'dni' => $dni,
    'nombre' => "Test Voluntario {$rand}",
    'email' => $email,
    'password' => 'password123',
    'zona' => 'Madrid',
    'experiencia' => 'Ninguna',
    'coche' => 'si',
    'idiomas' => ['Ingles'],
    'habilidades' => ['Programacion'],
    'intereses' => ['Educacion'],
    'fechaNacimiento' => '1990-01-01'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Registro Response Code: $httpCode\n";
echo "Registro Response Body: $response\n";

if ($httpCode === 201) {
    // Verificar el estado
    echo "\nVerificando estado (Buscando en lista completa)...\n";
    $verifyUrl = "http://127.0.0.1:8888/api/voluntarios";
    $ch2 = curl_init($verifyUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $response2 = curl_exec($ch2);
    curl_close($ch2);

    file_put_contents('verify_debug_response.txt', $response2);

    $json2 = json_decode($response2, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($json2)) {
         $msg = "ERROR JSON DECODE or NOT ARRAY: " . json_last_error_msg() . "\nBody excerpt: " . substr($response2, 0, 200) . "\n";
         echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
    } else {
        // Buscar el voluntario por email
        $found = null;
        foreach ($json2 as $v) {
            if (isset($v['correo']) && $v['correo'] === $email) {
                $found = $v;
                break;
            }
        }

        if ($found) {
             if (isset($found['estado_voluntario'])) {
                $msg = "Encontrado. Estado Voluntario: " . $found['estado_voluntario'] . "\n";
                echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
                if ($found['estado_voluntario'] === 'PENDIENTE') {
                    $msg = "VERIFICACIÓN EXITOSA: El estado es PENDIENTE por defecto.\n";
                    echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
                } else {
                    $msg = "VERIFICACIÓN FALLIDA: El estado no es PENDIENTE.\n";
                    echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
                }
            } else {
                $msg = "ERROR: No se encontró el campo 'estado_voluntario' en el objeto encontrado.\n";
                echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
            }
        } else {
             $msg = "ERROR: No se encontró el voluntario con email $email en la lista.\n";
             echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
        }
    }
} else {
    $msg = "ERROR: Falló el registro. Code: $httpCode. Body: $response\n";
    echo $msg; file_put_contents('verify_log.txt', $msg, FILE_APPEND);
}
