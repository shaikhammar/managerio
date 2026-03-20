# Stage 1: Build assets and dependencies
FROM dunglas/frankenphp:php8.4-alpine AS builder

# Install PHP extensions helper
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install PHP extensions
RUN install-php-extensions pdo_pgsql zip intl bcmath mbstring

# Install Node.js and npm
RUN apk add --no-cache nodejs npm zip unzip git

WORKDIR /app

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy only dependency files first for better caching
COPY composer.json composer.lock package*.json ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction
RUN npm install

# Copy the rest of the application
COPY . .

# Finish composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build assets
RUN npm run build

# Stage 2: Production runner
FROM dunglas/frankenphp:php8.4-alpine AS runner

# Install PHP extensions helper
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install PHP extensions for production
RUN install-php-extensions pdo_pgsql zip intl bcmath mbstring

# Optimized PHP Configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Configure FrankenPHP
ENV SERVER_NAME=":${PORT:-80}"
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_INI_SCAN_DIR=:/usr/local/etc/php/conf.d

WORKDIR /var/www/html

# Copy from builder (only what's needed for production)
COPY --from=builder /app /var/www/html

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
