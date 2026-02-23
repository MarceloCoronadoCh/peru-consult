# Multi-stage build for Peru Consult API
FROM php:7.4-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libxml2-dev \
    libcurl \
    && docker-php-ext-install \
    dom \
    json \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Production stage
FROM base AS production

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (production only, optimized)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    && composer clear-cache

# Copy application files
COPY . .

# Run composer scripts if any
RUN composer dump-autoload --optimize --no-dev

# Copy nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Create supervisor configuration
RUN mkdir -p /var/log/supervisor && \
    echo "[supervisord]" > /etc/supervisord.conf && \
    echo "nodaemon=true" >> /etc/supervisord.conf && \
    echo "user=root" >> /etc/supervisord.conf && \
    echo "" >> /etc/supervisord.conf && \
    echo "[program:php-fpm]" >> /etc/supervisord.conf && \
    echo "command=php-fpm -F" >> /etc/supervisord.conf && \
    echo "stdout_logfile=/dev/stdout" >> /etc/supervisord.conf && \
    echo "stdout_logfile_maxbytes=0" >> /etc/supervisord.conf && \
    echo "stderr_logfile=/dev/stderr" >> /etc/supervisord.conf && \
    echo "stderr_logfile_maxbytes=0" >> /etc/supervisord.conf && \
    echo "autorestart=true" >> /etc/supervisord.conf && \
    echo "" >> /etc/supervisord.conf && \
    echo "[program:nginx]" >> /etc/supervisord.conf && \
    echo "command=nginx -g 'daemon off;'" >> /etc/supervisord.conf && \
    echo "stdout_logfile=/dev/stdout" >> /etc/supervisord.conf && \
    echo "stdout_logfile_maxbytes=0" >> /etc/supervisord.conf && \
    echo "stderr_logfile=/dev/stderr" >> /etc/supervisord.conf && \
    echo "stderr_logfile_maxbytes=0" >> /etc/supervisord.conf && \
    echo "autorestart=true" >> /etc/supervisord.conf

# Configure PHP-FPM to run on port 9000
RUN echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisor to manage nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
