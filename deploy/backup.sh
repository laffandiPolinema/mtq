#!/usr/bin/env bash
#
# Backup database dan file bukti transfer ke /var/backups/mtq.
# Dijalankan otomatis setiap hari pukul 02.00 (cron dari setup-server.sh),
# atau manual:  sudo -u mtq bash /var/www/mtq/deploy/backup.sh
#
# Backup lebih dari KEEP_DAYS hari dihapus otomatis.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mtq}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%Y%m%d-%H%M%S)"

env_value() {
    grep -E "^$1=" "$APP_DIR/.env" | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

mkdir -p "$BACKUP_DIR"
umask 077

# Kredensial lewat file sementara agar password tidak terlihat di daftar proses.
CREDENTIALS="$(mktemp)"
trap 'rm -f "$CREDENTIALS"' EXIT
cat > "$CREDENTIALS" <<CNF
[client]
user=$(env_value DB_USERNAME)
password=$(env_value DB_PASSWORD)
host=$(env_value DB_HOST)
port=$(env_value DB_PORT)
CNF

mysqldump --defaults-extra-file="$CREDENTIALS" --single-transaction --no-tablespaces \
    "$(env_value DB_DATABASE)" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

tar -czf "$BACKUP_DIR/bukti-transfer-$STAMP.tar.gz" -C "$APP_DIR/storage/app" private

find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'bukti-transfer-*.tar.gz' \) \
    -mtime +"$KEEP_DAYS" -delete

echo "[$(date '+%F %T')] Backup selesai: db-$STAMP.sql.gz, bukti-transfer-$STAMP.tar.gz"
