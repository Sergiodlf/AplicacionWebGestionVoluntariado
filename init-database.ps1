# PowerShell script para inicializar la base de datos manualmente
# Ejecutar después de que Docker Compose esté corriendo

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Database Initialization Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Esperar a que SQL Server esté listo
Write-Host "Waiting for SQL Server..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Crear la base de datos si no existe
Write-Host "Creating database..." -ForegroundColor Green
$password = 'Volunt@ri0DB2024!'
# Usamos el operador --% para detener el parsing de PowerShell y pasar los argumentos tal cual
docker compose exec -T db --% /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P Volunt@ri0DB2024! -C -Q "IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = 'PROYECTOINTER') CREATE DATABASE PROYECTOINTER"

# Ejecutar migraciones
Write-Host "Running migrations..." -ForegroundColor Green
docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

# Cargar fixtures
Write-Host "Loading fixtures..." -ForegroundColor Green
docker compose exec -T backend php bin/console doctrine:fixtures:load --no-interaction --append

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Database initialized successfully!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Test users created:"
Write-Host " - Admin: admin@curso.com / admin123"
Write-Host " - Organization: organizacion_test@curso.com / 123456"
Write-Host " - Volunteer: voluntario_test@curso.com / 123456"
