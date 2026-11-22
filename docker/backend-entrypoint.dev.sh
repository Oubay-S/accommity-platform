#!/bin/sh
# Entrypoint du conteneur backend en mode développement.
# Objectif : un clone neuf démarre sans commande manuelle.
set -e

cd /var/www/backend

DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-accommity_user}"

echo "[backend] Attente de PostgreSQL sur ${DB_HOST}:${DB_PORT}..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" >/dev/null 2>&1; do
    sleep 2
done

if [ ! -f vendor/autoload.php ]; then
    echo "[backend] Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "[backend] Création de backend/.env depuis .env.example..."
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "[backend] Génération de APP_KEY..."
    php artisan key:generate --force
fi

php artisan storage:link >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[backend] Migrations et données de démonstration..."
    php artisan migrate --force
    php artisan db:seed --force
fi

echo "[backend] Prêt."
exec "$@"
