#!/bin/sh
set -e

PRIMARY="${PORT:-8080}"
case "$PRIMARY" in
    ''|*[!0-9]*) PRIMARY=8080 ;;
esac

# Listen on 80, 8080 and the platform's PORT so a target-port mismatch cannot
# produce a 502. Duplicates are removed before Apache starts.
PORTS="80 8080 ${PRIMARY}"
UNIQUE=""
for port in $PORTS; do
    case " ${UNIQUE} " in
        *" ${port} "*) ;;
        *) UNIQUE="${UNIQUE} ${port}" ;;
    esac
done

sed -ri '/^[[:space:]]*Listen[[:space:]]+[0-9]+[[:space:]]*$/d' /etc/apache2/ports.conf
VHOST_PORTS=""
for port in $UNIQUE; do
    printf 'Listen %s\n' "$port" >> /etc/apache2/ports.conf
    VHOST_PORTS="${VHOST_PORTS} *:${port}"
done

sed -ri "s#<VirtualHost \*:[0-9]+>#<VirtualHost${VHOST_PORTS}>#" \
    /etc/apache2/sites-available/000-default.conf

echo "Thunder Mounts: Apache listening on ports:${UNIQUE}"

# Runtime directories must stay writable even when a volume is mounted over
# public/uploads by the platform.
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/cache \
         /var/www/html/storage/sessions \
         /var/www/html/public/uploads/products \
         /var/www/html/public/uploads/dealers
chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

exec docker-php-entrypoint "$@"
