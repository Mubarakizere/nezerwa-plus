#!/usr/bin/env bash
set -e

echo "Running composer..."
composer install --no-dev --optimize-autoloader

echo "Clearing caches..."
php artisan optimize:clear || true

echo "Caching config..."
php artisan config:cache || true

echo "Caching routes..."
php artisan route:cache || true

echo "Caching views..."
php artisan view:cache || true

echo "Running migrations..."
php artisan migrate --force || true

echo "Deployment script finished."
