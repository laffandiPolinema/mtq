#!/usr/bin/env bash
#
# Deploy versi terbaru dari GitHub. Jalankan sebagai user aplikasi:
#
#   sudo -u mtq bash /var/www/mtq/deploy/deploy.sh
#
# Situs masuk mode maintenance selama proses dan otomatis kembali online,
# termasuk jika salah satu langkah gagal.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${BRANCH:-main}"
cd "$APP_DIR"

log() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }

[[ -f .env ]] || { echo "File .env belum ada. Jalankan deploy/setup-server.sh terlebih dahulu." >&2; exit 1; }

log "Mode maintenance"
php artisan down --retry=15 || true
trap 'php artisan up' EXIT

log "Mengambil kode terbaru (${BRANCH})"
git fetch --prune origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

log "Dependency PHP"
composer install --no-dev --optimize-autoloader --no-interaction

log "Build CSS/JS"
npm ci --no-audit --no-fund
npm run build

log "Migrasi database"
php artisan migrate --force

log "Cache konfigurasi, route, view"
php artisan optimize:clear
php artisan optimize

log "Selesai. Versi aktif: $(git log -1 --format='%h %s')"
