#!/usr/bin/env bash
# Despliegue tipidv.gridsoft.co en HostDime (200.7.105.79)
#
# GitHub Actions lo ejecuta remoto con:
#   ssh ubuntu@200.7.105.79 'bash -s' -- --migrate < deployment-scripts/deploy-tipidv.sh
#
# Manual en el servidor:
#   cd /home/ubuntu/www/app/tipidv.gridsoft.co && bash deploy.sh --migrate
#
set -euo pipefail

export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [[ -s "$NVM_DIR/nvm.sh" ]]; then
    # shellcheck source=/dev/null
    . "$NVM_DIR/nvm.sh"
    nvm use 24.3.0 2>/dev/null || nvm use default 2>/dev/null || true
fi

APP_PATH="/home/ubuntu/www/app/tipidv.gridsoft.co"

[[ -d "$APP_PATH" ]] || {
    echo "Error: no existe $APP_PATH"
    exit 1
}

cd "$APP_PATH"
git config --local --add safe.directory "$APP_PATH" 2>/dev/null || true

DEPLOY_ARGS=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --migrate) DEPLOY_ARGS+=(--migrate) ;;
        --no-pull) DEPLOY_ARGS+=(--no-pull) ;;
        --release)
            DEPLOY_ARGS+=(--release "$2")
            shift
            ;;
        *)
            echo "Opción desconocida: $1"
            exit 1
            ;;
    esac
    shift
done

[[ -f deploy.sh ]] || {
    echo "Error: falta deploy.sh en $APP_PATH"
    exit 1
}

bash deploy.sh "${DEPLOY_ARGS[@]}"
