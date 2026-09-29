#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Install PHP dependencies on first start (vendor/ lives in the bind mount).
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    echo "[entrypoint] Creating .env from .env.example..."
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --ansi
fi

# Expose storage/app/public (media files) at /storage.
if [ ! -e public/storage ]; then
    php artisan storage:link --ansi
fi

exec "$@"
