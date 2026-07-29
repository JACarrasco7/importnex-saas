FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql bcmath intl gd zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Fix MPM conflict in php:8.4-apache: remove ALL mpm modules first
# The base image ships with mpm_prefork enabled; we need ONLY mpm_event
# (php-fpm doesn't need mpm at all, but we use mod_php so we need one)
RUN rm -f /etc/apache2/mods-enabled/mpm_* && \
    echo "# MPM disabled by Dockerfile - we use mod_php via prefork" > /etc/apache2/mods-available/mpm_prefork.load.disabled && \
    echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" > /etc/apache2/mods-available/mpm_prefork.load && \
    a2enmod rewrite headers mpm_prefork || true

# Configure Apache
COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Configure PHP
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy package files
COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Use .env.production as template if no .env present
RUN cp -n .env.production .env || true

# Build assets
RUN npm run build

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
