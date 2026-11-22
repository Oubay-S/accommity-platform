#!/usr/bin/env bash
# Déploiement local Accommity (Linux / macOS / Git Bash).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

RESET=false
LOGS=false
BUILD=true

for arg in "$@"; do
    case "$arg" in
        --reset) RESET=true ;;
        --logs) LOGS=true ;;
        --no-build) BUILD=false ;;
        *) echo "Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

ENV_FILE="deploy/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "[deploy] Création de deploy/.env depuis deploy/.env.example..."
    cp deploy/.env.example "$ENV_FILE"
fi

if ! grep -q '^APP_KEY=base64:' "$ENV_FILE"; then
    if command -v openssl >/dev/null 2>&1; then
        KEY="$(openssl rand -base64 32 | tr -d '\n')"
    else
        KEY="$(head -c 32 /dev/urandom | base64 | tr -d '\n')"
    fi

    awk -v key="base64:${KEY}" '
        /^APP_KEY=/ { print "APP_KEY=" key; next }
        { print }
    ' "$ENV_FILE" > "$ENV_FILE.tmp"
    mv "$ENV_FILE.tmp" "$ENV_FILE"
    echo "[deploy] APP_KEY générée."
fi

COMPOSE=(docker compose -f docker-compose.deploy.yml --env-file deploy/.env)

if [ "$RESET" = true ]; then
    echo "[deploy] Suppression des conteneurs et volumes..."
    "${COMPOSE[@]}" down -v
fi

UP=("${COMPOSE[@]}" up -d)
if [ "$BUILD" = true ]; then
    UP+=(--build)
fi

echo "[deploy] Démarrage du stack local..."
"${UP[@]}"

echo "[deploy] Attente de l'API (max 5 min)..."
READY=false
for _ in $(seq 1 100); do
    if curl -fsS http://localhost:8080/api/annonces >/dev/null 2>&1; then
        READY=true
        break
    fi
    sleep 3
done

if [ "$READY" != true ]; then
    echo "[deploy] L'API ne répond pas. Derniers logs :" >&2
    "${COMPOSE[@]}" logs --tail=100
    exit 1
fi

if ! curl -fsS http://localhost:8080/ >/dev/null 2>&1; then
    echo "[deploy] Le frontend ne répond pas correctement." >&2
    "${COMPOSE[@]}" logs --tail=100 web
    exit 1
fi

cat <<'EOF'

Accommity est disponible :
  Application : http://localhost:8080
  API         : http://localhost:8080/api
  Mailpit     : http://localhost:8025

Comptes de démonstration :
  Admin    : admin@accommity.ma / Admin1234!
  Hôte     : hote.demo@accommity.ma / Password123!
  Voyageur : voyageur.demo@accommity.ma / Password123!

Commandes utiles :
  Logs   : ./scripts/deploy-local.sh --logs
  Reset  : ./scripts/deploy-local.sh --reset
  Arrêt  : docker compose -f docker-compose.deploy.yml --env-file deploy/.env down

EOF

if [ "$LOGS" = true ]; then
    "${COMPOSE[@]}" logs -f
fi
