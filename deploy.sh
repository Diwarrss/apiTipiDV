#!/usr/bin/env bash
#
# Despliegue TipiDV (tipidv.gridsoft.co)
# Uso:
#   ./deploy.sh
#   ./deploy.sh --release build-18
#   ./deploy.sh --repair build-17
#   ./deploy.sh --migrate
#   ./deploy.sh --no-pull
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

DO_PULL=1
DO_MIGRATE=0
RELEASE_TAG=""
REPAIR_TAG=""

usage() {
    sed -n '2,8p' "$0" | sed 's/^# \?//'
    echo ""
    echo "Opciones:"
    echo "  --no-pull          No ejecuta git pull"
    echo "  --migrate          Ejecuta php artisan migrate --force"
    echo "  --release TAG      Sincroniza instalador: tipidv:sync-release TAG"
    echo "  --repair TAG       Repara metadata: tipidv:release-repair TAG"
    echo "  -h, --help         Muestra esta ayuda"
}

log()  { echo -e "${GREEN}▶${NC} $*"; }
warn() { echo -e "${YELLOW}⚠${NC} $*"; }
fail() { echo -e "${RED}✗${NC} $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-pull) DO_PULL=0 ;;
        --migrate) DO_MIGRATE=1 ;;
        --release)
            [[ $# -ge 2 ]] || fail "Falta TAG en --release"
            RELEASE_TAG="$2"
            shift
            ;;
        --repair)
            [[ $# -ge 2 ]] || fail "Falta TAG en --repair"
            REPAIR_TAG="$2"
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            fail "Opción desconocida: $1 (usa --help)"
            ;;
    esac
    shift
done

[[ -f artisan ]] || fail "No se encontró artisan. Ejecuta el script desde la raíz del proyecto."

command -v php >/dev/null 2>&1 || fail "php no está instalado o no está en PATH"
command -v composer >/dev/null 2>&1 || fail "composer no está instalado"
command -v npm >/dev/null 2>&1 || fail "npm no está instalado"

echo ""
log "Despliegue TipiDV — $(date '+%Y-%m-%d %H:%M:%S')"
log "Directorio: $ROOT"
echo ""

if [[ "$DO_PULL" -eq 1 ]]; then
    log "git pull"
    git pull --ff-only
else
    warn "Omitido: git pull"
fi

log "composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader --no-interaction

log "npm ci && npm run build"
npm ci
npm run build

if [[ "$DO_MIGRATE" -eq 1 ]]; then
    log "php artisan migrate --force"
    php artisan migrate --force
fi

if [[ -d storage/app/private/releases ]]; then
    log "Permisos storage/app/private/releases"
    chmod 755 storage/app/private/releases 2>/dev/null || warn "No se pudo chmod releases/ (¿sudo?)"
    if [[ -f storage/app/private/releases/TipiDV-Setup.exe ]]; then
        chmod 644 storage/app/private/releases/TipiDV-Setup.exe 2>/dev/null || true
    fi
fi

if [[ -n "$RELEASE_TAG" ]]; then
    log "php artisan tipidv:sync-release ${RELEASE_TAG}"
    php artisan tipidv:sync-release "$RELEASE_TAG"
elif [[ -n "$REPAIR_TAG" ]]; then
    log "php artisan tipidv:release-repair ${REPAIR_TAG}"
    php artisan tipidv:release-repair "$REPAIR_TAG"
fi

log "php artisan optimize:clear"
php artisan optimize:clear

log "php artisan config:cache"
php artisan config:cache

log "php artisan route:cache"
php artisan route:cache

log "php artisan view:cache"
php artisan view:cache

if php artisan list --raw 2>/dev/null | grep -q '^tipidv:release-status$'; then
    echo ""
    log "Estado de descarga"
    php artisan tipidv:release-status || true
fi

echo ""
echo -e "${GREEN}✓ Despliegue completado${NC}"
echo "  Sitio: ${APP_URL:-https://tipidv.gridsoft.co}"
echo ""
