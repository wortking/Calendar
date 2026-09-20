# Imagen de producción: a diferencia de docker/php/Dockerfile (pensado para
# dev, donde el código llega por bind mount), acá el código se copia y se
# compila dentro de la imagen, y nginx + php-fpm + el worker de mails
# conviven en el mismo contenedor (vía supervisord) para que sea UN solo
# servicio desplegable, sin bind mounts ni contenedores separados.

FROM php:8.3-fpm-alpine

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
    supervisor \
    nginx \
    gettext

RUN docker-php-ext-install pdo_pgsql zip opcache
RUN pecl install redis apcu && docker-php-ext-enable redis apcu

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar solo los manifiestos primero para cachear "composer install" entre
# builds cuando el código cambia pero las dependencias no.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p var/cache var/log public/uploads/avatars config/jwt \
    && chown -R www-data:www-data var public/uploads

COPY docker/prod/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/prod/supervisord.conf /etc/supervisord.conf
COPY docker/prod/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/prod/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV APP_ENV=prod

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
