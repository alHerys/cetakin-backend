FROM php:8.3-cli
RUN apt-get update && apt-get install -y libpq-dev libcurl4-openssl-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql curl zip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN chmod +x start.sh
CMD ["sh", "start.sh"]
