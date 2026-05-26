#!/bin/sh
touch .env
php artisan migrate --force
echo "PORT=${PORT}"
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
echo "serve exited with code $?"
