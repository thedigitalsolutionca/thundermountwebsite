# Thunder Mounts — PHP 8.3 + Apache image for GitHub-connected hosts
# (Railway, Render, Fly.io, DigitalOcean App Platform, any Docker host).

FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd mbstring \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

# Serve only public/ as the document root.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html

COPY docker/apache-thunder.conf /etc/apache2/conf-available/thunder.conf
COPY docker/entrypoint.sh /usr/local/bin/thunder-entrypoint
RUN a2enconf thunder \
    && chmod +x /usr/local/bin/thunder-entrypoint \
    && mkdir -p storage/logs storage/cache storage/sessions \
        public/uploads/products public/uploads/dealers \
    && chown -R www-data:www-data storage public/uploads

EXPOSE 8080
ENTRYPOINT ["thunder-entrypoint"]
CMD ["apache2-foreground"]
