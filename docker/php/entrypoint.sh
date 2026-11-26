#!/bin/sh
set -e

READY_FLAG=storage/framework/.bootstrapped

if [ "${ROLE:-app}" = "app" ]; then
    rm -f "$READY_FLAG"

    [ -f .env ] || cp .env.example .env
    [ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist

    if ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force
    fi

    echo "Waiting for MySQL..."
    until php artisan migrate --force --no-interaction; do sleep 3; done

    php artisan storage:link || true
    chown -R www-data:www-data storage bootstrap/cache

    touch "$READY_FLAG"
else
    # worker / scheduler: wait until the app container has installed deps and migrated.
    echo "Waiting for app bootstrap..."
    until [ -f "$READY_FLAG" ]; do sleep 2; done
fi

exec "$@"
