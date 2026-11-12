#!/bin/sh
set -e

PORT="${PORT:-8080}"

# Railway/Render inject a dynamic port; make Apache listen on it.
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Runtime directories must stay writable even when a volume is mounted over
# public/uploads by the platform.
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/cache \
         /var/www/html/storage/sessions \
         /var/www/html/public/uploads/products \
         /var/www/html/public/uploads/dealers
chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

exec docker-php-entrypoint "$@"
