#!/bin/bash
set -e

# Wait for database to be ready (if using PostgreSQL/MySQL)
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "Waiting for database connection..."
    sleep 5
fi

# Cache config/routes at runtime when env vars are available
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (skip if database not available)
php artisan migrate --force || echo "Warning: Migration failed, continuing..."

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
