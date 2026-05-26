#!/bin/sh

# Write env vars to .env so Laravel can read them
printenv | grep -E "^(APP_|DB_|JWT_|CLOUDINARY_|LOG_|SESSION_|CACHE_|QUEUE_|BROADCAST_|FILESYSTEM_)" | while IFS='=' read -r key value; do
    echo "$key=\"$value\""
done > .env

php artisan config:clear
php artisan migrate --force
exec php -S 0.0.0.0:${PORT:-8000} -t public public/index.php
