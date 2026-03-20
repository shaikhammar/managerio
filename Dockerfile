# Stage 1: Build assets
FROM node:22-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production environment
FROM dunglas/frankenphp:php8.4-alpine AS runner

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev \
    oniguruma-dev

# Install PHP extensions for production performance
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    bcmath \
    opcache \
    mbstring

# Optimized PHP Configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Configure FrankenPHP
# We use the PORT environment variable provided by Railway/Cloud providers
ENV SERVER_NAME=":{$PORT:-80}"
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_INI_SCAN_DIR=:/usr/local/etc/php/conf.d

WORKDIR /var/www/html

# Copy application files
COPY . .
COPY --from=assets-builder /app/public/build ./public/build

# Install composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
