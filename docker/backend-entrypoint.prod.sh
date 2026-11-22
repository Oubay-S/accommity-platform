#!/bin/sh
# Entrypoint du conteneur backend en mode déploiement local.
# Le scheduler réutilise la même image avec SKIP_BOOTSTRAP=true.
set -e

cd /var/www/backend

if [ "${SKIP_BOOTSTRAP:-false}" != "true" ]; then
    DB_HOST="${DB_HOST:-postgres}"
    DB_PORT="${DB_PORT:-5432}"
    DB_USERNAME="${DB_USERNAME:-accommity_user}"

    echo "[backend] Attente de PostgreSQL sur ${DB_HOST}:${DB_PORT}..."
    until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" >/dev/null 2>&1; do
        sleep 2
    done

    php artisan package:discover --ansi
    php artisan migrate --force
    php artisan db:seed --force
    php artisan storage:link >/dev/null 2>&1 || true

    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache

    chown -R www-data:www-data storage bootstrap/cache
fi

echo "[backend] Prêt."
exec "$@"
