# Production di VPS

Panduan memasang aplikasi pendaftaran MTQ Kota Malang 2026 di VPS atau server sendiri.

## Kebutuhan server

| | Minimum |
|---|---|
| Sistem operasi | Ubuntu 22.04 atau 24.04 (baru/bersih) |
| RAM | 1 GB (2 GB disarankan) |
| Disk | 10 GB |
| Akses | SSH dengan user root atau sudo |
| Port terbuka | 22 (SSH), 80 (HTTP), 443 (HTTPS) |

Script akan memasang: Nginx, MySQL 8, PHP 8.4 (dari PPA ondrej, karena dependency butuh PHP ≥ 8.4.1), Composer, Node.js 22, dan UFW.

## 1. Setup awal (sekali saja)

Login ke server, lalu jalankan:

```sh
curl -fsSL https://raw.githubusercontent.com/laffandiPolinema/mtq/main/deploy/setup-server.sh -o setup-server.sh
sudo bash setup-server.sh
```

Proses ±5–10 menit. Script akan:

- memasang semua paket di atas;
- membuat database `mtq_kota_malang` dan user `mtq` dengan password acak;
- mengunduh kode ke `/var/www/mtq` dan membuat `.env` production (`APP_DEBUG=false`, zona waktu WIB);
- membangun CSS/JS, menjalankan migrasi, dan mengatur Nginx;
- mengaktifkan firewall dan backup harian pukul 02.00.

Setelah selesai, website bisa dibuka lewat `http://IP-SERVER`.

> Jika ingin memakai domain sejak awal: `sudo SERVER_NAME=mtq.contoh.id bash setup-server.sh`

## 2. Buat akun panitia

```sh
sudo -u mtq php /var/www/mtq/artisan mtq:make-admin
```

Login di `http://IP-SERVER/admin/login`.

## 3. Aktifkan HTTPS (setelah punya domain)

1. Di pengelola domain, buat **DNS record A** yang mengarah ke IP server.
2. Tunggu beberapa menit sampai domain mengarah ke server (cek: `ping domain-anda.id`).
3. Jalankan:

   ```sh
   sudo bash /var/www/mtq/deploy/enable-https.sh domain-anda.id email@anda.id
   ```

Script memasang sertifikat Let's Encrypt (gratis, diperpanjang otomatis), mengalihkan HTTP ke HTTPS, dan memperbarui `APP_URL` di `.env`.

## 4. Update aplikasi

Setelah ada perubahan yang di-push ke GitHub:

```sh
sudo -u mtq bash /var/www/mtq/deploy/deploy.sh
```

Selama ±1 menit situs menampilkan halaman maintenance, lalu kembali online, termasuk jika ada langkah yang gagal.

## Backup

- Otomatis setiap hari pukul 02.00 ke `/var/backups/mtq`, berisi dump database dan semua file bukti transfer. Backup lebih dari 14 hari dihapus otomatis.
- Manual: `sudo -u mtq bash /var/www/mtq/deploy/backup.sh`
- Log backup: `/var/backups/mtq/backup.log`

Backup masih tersimpan di server yang sama. Salin berkala ke tempat lain (Google Drive, komputer panitia) agar aman jika server bermasalah, misalnya dari komputer Anda:

```sh
scp -r user@IP-SERVER:/var/backups/mtq ./backup-mtq
```

**Memulihkan database dari backup:**

```sh
gunzip -c /var/backups/mtq/db-YYYYMMDD-HHMMSS.sql.gz | sudo mysql mtq_kota_malang
```

## Pemecahan masalah

| Gejala | Cek |
|---|---|
| Halaman error 500 | `tail -50 /var/www/mtq/storage/logs/laravel-$(date +%F).log` |
| Tidak bisa dibuka sama sekali | `sudo systemctl status nginx php8.4-fpm mysql` |
| Upload bukti gagal | Ukuran file > 5 MB, atau cek log Nginx: `sudo tail /var/log/nginx/error.log` |
| Perubahan `.env` tidak berlaku | `sudo -u mtq php /var/www/mtq/artisan optimize` |

## Isi folder ini

| File | Fungsi |
|---|---|
| `setup-server.sh` | Setup awal server (sekali jalan, aman diulang) |
| `deploy.sh` | Update ke versi terbaru dari GitHub |
| `enable-https.sh` | Pasang SSL setelah domain siap |
| `backup.sh` | Backup database + bukti transfer |
| `nginx-mtq.conf` | Template konfigurasi Nginx |
| `../.env.production.example` | Template `.env` production |
