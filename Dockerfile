# UniStay: PHP/Apache container for Render's Docker web service.
# The PHP version is aligned with the project's PHP 8.5 development runtime.
FROM php:8.5-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl git unzip libcurl4-openssl-dev libonig-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli curl mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . /var/www/html/

# Packages (including Google authentication dependencies) are installed at build time.
# Never bake credentials into image layers or ARGs.
RUN if [ -f composer.json ]; then \
        COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist \
        --no-interaction --no-progress --optimize-autoloader; \
    fi

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/unistay.ini
RUN a2enmod headers rewrite && chmod +x /var/www/html/docker/entrypoint.sh \
    && mkdir -p /var/www/html/config

ENV PORT=10000
EXPOSE 10000
CMD ["bash", "/var/www/html/docker/entrypoint.sh"]
