#!/bin/bash
set -e

# Las claves JWT no se versionan (ver .gitignore); en Railway se pasan como
# variables de entorno en base64 y acá se escriben a disco antes de arrancar.
if [ -n "$JWT_PRIVATE_KEY_BASE64" ] && [ ! -f config/jwt/private.pem ]; then
    echo "$JWT_PRIVATE_KEY_BASE64" | base64 -d > config/jwt/private.pem
fi
if [ -n "$JWT_PUBLIC_KEY_BASE64" ] && [ ! -f config/jwt/public.pem ]; then
    echo "$JWT_PUBLIC_KEY_BASE64" | base64 -d > config/jwt/public.pem
fi

# Railway asigna el puerto público en $PORT en tiempo de arranque (no se
# conoce en build time), así que el nginx.conf real se genera recién acá.
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console cache:clear --env=prod --no-debug

exec "$@"
