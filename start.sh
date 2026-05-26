#!/bin/sh

echo "PORT is: ${PORT}"
touch .env

php artisan migrate --force 2>&1

echo "Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000} 2>&1
