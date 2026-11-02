#!/usr/bin/env bash
#
# Aktifkan HTTPS (Let's Encrypt) setelah domain diarahkan ke IP server.
#
#   sudo bash /var/www/mtq/deploy/enable-https.sh mtq.contoh.id email@contoh.id
#
# Email dipakai Let's Encrypt untuk pemberitahuan sertifikat yang akan kedaluwarsa.
# Sertifikat diperpanjang otomatis oleh timer certbot.

set -euo pipefail

DOMAIN="${1:-}"
EMAIL="${2:-}"
APP_USER="mtq"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NGINX_SITE="/etc/nginx/sites-available/mtq"

die() { printf '\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Jalankan sebagai root (sudo)."
[[ -n "$DOMAIN" && -n "$EMAIL" ]] || die "Pemakaian: sudo bash $0 domain-anda.id email@anda.id"
[[ -f "$NGINX_SITE" ]] || die "Konfigurasi Nginx belum ada. Jalankan deploy/setup-server.sh terlebih dahulu."

SERVER_IP="$(curl -fsS4 --max-time 5 https://ifconfig.me || true)"
DOMAIN_IP="$(getent ahostsv4 "$DOMAIN" | awk 'NR==1 {print $1}')"
if [[ -n "$SERVER_IP" && "$DOMAIN_IP" != "$SERVER_IP" ]]; then
    die "Domain ${DOMAIN} mengarah ke '${DOMAIN_IP:-tidak ada}', bukan ke server ini (${SERVER_IP}). Atur DNS record A dulu, tunggu beberapa menit, lalu ulangi."
fi

echo "==> Memasang certbot"
apt-get install -y certbot python3-certbot-nginx

echo "==> Mengganti server_name Nginx menjadi ${DOMAIN}"
sed -i -E "s|^(\s*server_name)\s+[^;]+;|\1 ${DOMAIN};|" "$NGINX_SITE"
nginx -t && systemctl reload nginx

echo "==> Meminta sertifikat SSL"
certbot --nginx --non-interactive --agree-tos --redirect -m "$EMAIL" -d "$DOMAIN"

echo "==> Memperbarui .env (APP_URL https, cookie aman)"
sed -i -E "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" "$APP_DIR/.env"
if grep -q '^SESSION_SECURE_COOKIE=' "$APP_DIR/.env"; then
    sed -i -E "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" "$APP_DIR/.env"
else
    echo "SESSION_SECURE_COOKIE=true" >> "$APP_DIR/.env"
fi
sudo -u "$APP_USER" -H bash -c "cd '$APP_DIR' && php artisan optimize"

echo
echo "Selesai! Website aktif di https://${DOMAIN}"
