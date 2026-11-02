#!/usr/bin/env bash
#
# Setup awal server production MTQ Kota Malang 2026 (sekali jalan).
# Diuji untuk Ubuntu 22.04 / 24.04 yang masih bersih. Jalankan sebagai root:
#
#   curl -fsSL https://raw.githubusercontent.com/laffandiPolinema/mtq/main/deploy/setup-server.sh -o setup-server.sh
#   sudo bash setup-server.sh
#
# Variabel opsional:
#   SERVER_NAME  domain atau IP publik (default: IP server terdeteksi otomatis)
#   APP_DIR      lokasi aplikasi        (default: /var/www/mtq)
#   REPO_URL     repository git         (default: https://github.com/laffandiPolinema/mtq.git)
#   BRANCH       branch yang di-deploy  (default: main)
#
# Script ini aman dijalankan ulang: langkah yang sudah selesai akan dilewati.

set -euo pipefail

PHP_VERSION="8.4"
NODE_MAJOR="22"
APP_USER="mtq"
APP_DIR="${APP_DIR:-/var/www/mtq}"
REPO_URL="${REPO_URL:-https://github.com/laffandiPolinema/mtq.git}"
BRANCH="${BRANCH:-main}"
DB_NAME="mtq_kota_malang"
DB_USER="mtq"

log() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Jalankan sebagai root: sudo bash $0"
command -v apt-get >/dev/null || die "Script ini hanya untuk Ubuntu/Debian."

SERVER_NAME="${SERVER_NAME:-$(curl -fsS4 --max-time 5 https://ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')}"
[[ -n "$SERVER_NAME" ]] || die "Tidak bisa mendeteksi IP server. Jalankan ulang dengan SERVER_NAME=domain-atau-ip."

export DEBIAN_FRONTEND=noninteractive

log "Memasang paket sistem (Nginx, MySQL, PHP ${PHP_VERSION}, Git, UFW)"
apt-get update -y
apt-get install -y software-properties-common ca-certificates curl gnupg unzip git ufw acl
if ! grep -rq "ondrej/php" /etc/apt/sources.list.d/ 2>/dev/null; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
apt-get install -y nginx mysql-server \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-opcache"

log "Mengatur PHP (batas upload bukti transfer, opcache)"
cat > "/etc/php/${PHP_VERSION}/fpm/conf.d/99-mtq.ini" <<'INI'
upload_max_filesize = 8M
post_max_size = 10M
memory_limit = 256M
expose_php = Off
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
INI
systemctl restart "php${PHP_VERSION}-fpm"

if ! command -v composer >/dev/null; then
    log "Memasang Composer"
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [[ "$EXPECTED" == "$ACTUAL" ]] || die "Checksum installer Composer tidak cocok."
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

if ! command -v node >/dev/null || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt "$NODE_MAJOR" ]]; then
    log "Memasang Node.js ${NODE_MAJOR} (untuk build CSS/JS)"
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
fi

log "Menyiapkan user aplikasi '${APP_USER}'"
if ! id "$APP_USER" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash --groups www-data "$APP_USER"
fi

log "Mengambil kode dari ${REPO_URL} (${BRANCH})"
if [[ ! -d "$APP_DIR/.git" ]]; then
    mkdir -p "$(dirname "$APP_DIR")"
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
chown -R "$APP_USER":www-data "$APP_DIR"

if [[ ! -f "$APP_DIR/.env" ]]; then
    log "Membuat database dan file .env production"
    DB_PASSWORD="$(openssl rand -hex 24)"
    mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
    sed -e "s|__APP_HOST__|${SERVER_NAME}|" -e "s|__DB_PASSWORD__|${DB_PASSWORD}|" \
        "$APP_DIR/.env.production.example" > "$APP_DIR/.env"
    chown "$APP_USER":www-data "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
else
    log ".env sudah ada, database dan .env tidak diubah"
fi

log "Mengatur hak akses folder storage"
for dir in storage bootstrap/cache; do
    setfacl -R -m u:www-data:rwX -m u:"$APP_USER":rwX "$APP_DIR/$dir"
    setfacl -dR -m u:www-data:rwX -m u:"$APP_USER":rwX "$APP_DIR/$dir"
done

log "Build dan migrasi pertama"
sudo -u "$APP_USER" -H bash -c "cd '$APP_DIR' && composer install --no-dev --optimize-autoloader --no-interaction"
if ! grep -qE '^APP_KEY=base64:' "$APP_DIR/.env"; then
    sudo -u "$APP_USER" -H bash -c "cd '$APP_DIR' && php artisan key:generate --force"
fi
sudo -u "$APP_USER" -H bash "$APP_DIR/deploy/deploy.sh"

log "Mengatur Nginx untuk ${SERVER_NAME}"
sed -e "s|__SERVER_NAME__|${SERVER_NAME}|" -e "s|__APP_DIR__|${APP_DIR}|" \
    -e "s|__PHP_SOCK__|/run/php/php${PHP_VERSION}-fpm.sock|" \
    "$APP_DIR/deploy/nginx-mtq.conf" > /etc/nginx/sites-available/mtq
ln -sf /etc/nginx/sites-available/mtq /etc/nginx/sites-enabled/mtq
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

log "Mengaktifkan firewall (SSH, HTTP, HTTPS)"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null

log "Menjadwalkan backup harian pukul 02.00"
mkdir -p /var/backups/mtq
chown "$APP_USER":"$APP_USER" /var/backups/mtq
chmod 700 /var/backups/mtq
echo "0 2 * * * ${APP_USER} bash ${APP_DIR}/deploy/backup.sh >> /var/backups/mtq/backup.log 2>&1" > /etc/cron.d/mtq-backup

cat <<DONE

==============================================================
  Selesai! Aplikasi dapat dibuka di: http://${SERVER_NAME}

  Langkah berikutnya:
  1. Buat akun panitia:
       sudo -u ${APP_USER} php ${APP_DIR}/artisan mtq:make-admin
  2. Setelah domain diarahkan ke server ini, aktifkan HTTPS:
       sudo bash ${APP_DIR}/deploy/enable-https.sh domain-anda.id email@anda.id
  3. Untuk update dari GitHub di kemudian hari:
       sudo -u ${APP_USER} bash ${APP_DIR}/deploy/deploy.sh
==============================================================
DONE
