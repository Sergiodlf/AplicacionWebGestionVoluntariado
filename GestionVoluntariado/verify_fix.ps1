$timestamp = Get-Date -Format "HHmmss"
$dni = "99" + $timestamp + "Z"
$email = "test.volunteer." + $timestamp + "@example.com"

$body = @{
    dni = $dni
    nombre = "Test Volunteer"
    email = $email
    password = "password123"
    zona = "Centro"
    fechaNacimiento = "1995-05-15"
    experiencia = "Testing"
    coche = $true
    habilidades = @(1, 2)
    intereses = @(1)
    idiomas = @("Inglés", "Francés")
    disponibilidad = @("Lunes Mañana", "Martes Tarde")
    ciclo = "Desarrollo de Aplicaciones Web (DAW)"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/auth/register/voluntario" -Method Post -Body $body -ContentType "application/json"

$response = Invoke-RestMethod -Uri "http://localhost:8000/api/ciclos" -Method Get
Write-Host "Ciclos found:"
$response | ConvertTo-Json -Depth 3 | Write-Host
