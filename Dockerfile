FROM php:8.3-cli
RUN apt-get update && apt-get install -y libpq-dev libcurl4-openssl-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql curl zip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
CMD until php artisan migrate --force; do echo "Waiting for DB..."; sleep 3; done && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
