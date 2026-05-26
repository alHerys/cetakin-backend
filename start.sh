#!/bin/sh

touch .env
php artisan migrate --force
php -S 0.0.0.0:${PORT:-8000} -t public/
