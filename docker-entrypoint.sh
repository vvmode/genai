#!/bin/bash
set -e

# Cache config/routes at runtime when env vars are available
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
