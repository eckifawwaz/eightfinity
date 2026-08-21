#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

if [[ ! -f .env ]]; then
    echo "Missing .env. Copy .env.example to .env on the server and fill production values first." >&2
    exit 1
fi

if grep -Eq '^APP_ENV=(local|testing)$' .env; then
    echo "Refusing to deploy with APP_ENV=local/testing. Set APP_ENV=production in .env first." >&2
    exit 1
fi

if [[ -f public/hot ]]; then
    rm public/hot
fi

composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Production deploy steps completed."
