#!/bin/bash
# Script para inicializar la base de datos manualmente
# Ejecutar después de que Docker Compose esté corriendo

echo "=========================================="
echo " Database Initialization Script"
echo "=========================================="

# esperar a que SQL Server esté listo
echo "⏳ Waiting for SQL Server..."
sleep 10

# Crear la base de datos si no existe
echo "📦 Creating database..."
docker compose exec -T backend php bin/console dbal:run-sql "IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = 'PROYECTOINTER') CREATE DATABASE PROYECTOINTER" || echo "Database might already exist"

# Ejecutar migraciones
echo "📦 Running migrations..."
docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction

# Cargar fixtures
echo "🌱 Loading fixtures..."
docker compose exec -T backend php bin/console doctrine:fixtures:load --no-interaction --append

echo "=========================================="
echo " ✅ Database initialized successfully!"
echo "=========================================="
echo ""
echo "Test users created:"
echo " - Admin: admin@curso.com / admin123"
echo " - Organization: organizacion_test@curso.com / 123456"
echo " - Volunteer: voluntario_test@curso.com / 123456"
