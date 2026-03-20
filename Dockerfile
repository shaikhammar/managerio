# Stage 1: Build assets and dependencies
FROM dunglas/frankenphp:php8.4-alpine AS builder

# Install Node.js and npm
RUN apk add --no-cache nodejs npm

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    bcmath \
    mbstring

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

# Finish composer (generate autoloader and run scripts)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build assets (Wayfinder will now find the 'php' binary)
RUN npm run build

# Stage 2: Production runner
FROM dunglas/frankenphp:php8.4-alpine AS runner

# Install system runtime dependencies
RUN apk add --no-cache \
    libpq \
    libzip \
    icu-libs

# Install PHP extensions for production
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    bcmath \
    opcache

# Optimized PHP Configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Configure FrankenPHP
ENV SERVER_NAME=":{$PORT:-80}"
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_INI_SCAN_DIR=:/usr/local/etc/php/conf.d

WORKDIR /var/www/html

# Copy from builder
COPY --from=builder /app /var/www/html

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
