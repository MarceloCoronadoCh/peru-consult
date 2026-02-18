FROM php:8.2-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install dom curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar proyecto
WORKDIR /var/www
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Comando por defecto (puede ser sobreescrito)
CMD ["php", "-a"]
