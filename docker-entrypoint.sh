#!/bin/sh
set -e

# Dynamically set SERVER_NAME to use the port provided by Railway/Cloud providers at runtime
# We use http:// to ensure Caddy treats it as a standard HTTP listener
export SERVER_NAME="http://:${PORT:-80}"

# Clear and rebuild caches
php artisan config:cache
# php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations automatically on deployment
php artisan migrate --force

exec "$@"
