#!/bin/sh
set -e

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations automatically on deployment
# The --force flag is required for production
php artisan migrate --force

exec "$@"
