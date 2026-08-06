#!/bin/sh
set -eu

mkdir -p \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage

# php-fpm startet als root und dropt Privilegien intern je Pool-Konfiguration
# (pm.user=www-data). Alle anderen Kommandos - insbesondere der Discovery-
# Queue-Worker - laufen bewusst nicht als root.
if [ "$1" = "php-fpm" ]; then
    exec docker-php-entrypoint "$@"
fi

exec su -s /bin/sh -c "docker-php-entrypoint $*" www-data