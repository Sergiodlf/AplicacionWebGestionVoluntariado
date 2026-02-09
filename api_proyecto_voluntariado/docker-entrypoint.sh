#!/bin/bash
set -e

echo "🚀 Starting PHP-FPM..."
echo "ℹ️  Run migrations manually with: docker compose exec backend php bin/console doctrine:migrations:migrate"

# Iniciar PHP-FPM
exec php-fpm
