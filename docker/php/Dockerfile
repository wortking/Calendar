FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema y herramientas de compilación
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    bash \
    autoconf \
    g++ \
    make \
    supervisor

# Instalar extensiones de PHP necesarias
RUN docker-php-ext-install pdo_pgsql zip opcache

# Instalar y activar la extensión de Redis y APCu mediante PECL
RUN pecl install redis apcu && \
    docker-php-ext-enable redis apcu

# Copiar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/supervisord.conf /etc/supervisord.conf
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

EXPOSE 9000
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
