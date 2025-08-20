# Dockerfile
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip git libzip-dev zip curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install

# Make artisan executable
RUN chmod +x artisan
