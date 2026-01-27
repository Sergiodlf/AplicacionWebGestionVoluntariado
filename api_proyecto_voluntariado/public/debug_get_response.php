<?php
// Script to test the API response directly from the server
$url = 'http://127.0.0.1:8000/api/actividades';

echo "Fetching URL: $url\n";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: DebugScript/1.0\r\n"
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error fetching URL.\n";
    exit(1);
}

$data = json_decode($response, true);
if (!$data) {
    echo "Error decoding JSON response.\n";
    echo "Raw Response: " . substr($response, 0, 500) . "...\n";
    exit(1);
}

echo "Response Data Count: " . count($data) . "\n\n";

// Check the first 3 items for description
$count = 0;
foreach ($data as $item) {
    echo "ID: " . ($item['codActividad'] ?? 'N/A') . "\n";
    echo "Name: " . ($item['nombre'] ?? 'N/A') . "\n";
    
    if (array_key_exists('descripcion', $item)) {
        echo "Description: '" . $item['descripcion'] . "' [FOUND]\n";
    } else {
        echo "Description: [MISSING KEY]\n";
    }
    echo "------------------------------------------------\n";
    
    $count++;
    if ($count >= 5) break; 
}
