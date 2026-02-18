FROM php:7.4-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    nginx \
    && docker-php-ext-install zip dom curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar proyecto
WORKDIR /var/www
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data /var/www

# Nginx config
COPY nginx.conf /etc/nginx/sites-available/default

EXPOSE 80

# Iniciar PHP-FPM en background y Nginx en foreground
CMD php-fpm -D && nginx -g 'daemon off;'
