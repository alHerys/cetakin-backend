#!/bin/sh

echo "Generating .env from environment variables..."
php -r "
\$vars = [
    'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL',
    'APP_LOCALE', 'APP_FALLBACK_LOCALE', 'APP_FAKER_LOCALE',
    'APP_MAINTENANCE_DRIVER', 'BCRYPT_ROUNDS',
    'LOG_CHANNEL', 'LOG_STACK', 'LOG_DEPRECATIONS_CHANNEL', 'LOG_LEVEL',
    'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
    'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_ENCRYPT', 'SESSION_PATH', 'SESSION_DOMAIN',
    'BROADCAST_CONNECTION', 'FILESYSTEM_DISK', 'QUEUE_CONNECTION',
    'CACHE_STORE', 'MEMCACHED_HOST',
    'REDIS_CLIENT',
    'JWT_SECRET', 'JWT_TTL', 'JWT_REFRESH_TTL',
    'CLOUDINARY_URL',
];
\$lines = [];
foreach (\$vars as \$key) {
    \$val = getenv(\$key);
    if (\$val !== false) {
        \$lines[] = \$key . '=\"' . addslashes(\$val) . '\"';
    }
}
file_put_contents('.env', implode(PHP_EOL, \$lines) . PHP_EOL);
echo 'Done.' . PHP_EOL;
"

echo "Running migrations..."
php artisan migrate --force

echo "Starting server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
