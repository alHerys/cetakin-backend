#!/bin/sh

touch .env

php artisan migrate --force 2>&1

exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000} 2>&1
