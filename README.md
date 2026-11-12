# Pendaftaran MTQ Kota Malang 2026

Aplikasi pendaftaran peserta Musabaqah Tilawatil Qur’an Tingkat Kota Malang 2026, diselenggarakan PHBI & Ketakmiran Masjid Jami’ Blimbing.

## Fitur

- Pendaftaran peserta umum atau perwakilan lembaga dari lima kecamatan di Kota Malang.
- Pilihan cabang sesuai proposal: tilawah (anak-anak, remaja, dewasa, lansia), khot indah Al-Qur’an, MHQ, dan murottal.
- Tanpa akun peserta: status dan unggah bukti pembayaran dibuka dengan kode pendaftaran + nomor WhatsApp terdaftar (format 08…, 62…, atau +62… dianggap sama). Akses berlaku per sesi browser.
- Biaya pendaftaran per kategori (Tilawah: Anak Rp35.000, Remaja Rp50.000, Dewasa Rp70.000; MHQ Juz 30 dan Murottal tingkat SD: Rp35.000) + kode unik 3 digit (001–999, tidak ada yang sama antarpeserta) sebagai penanda asal transfer. Cabang, kategori, biaya, jadwal pendaftaran, dan rekening diatur di `config/mtq.php`.
- Cabang sesuai juknis: MTQ Tilawah (Anak, Remaja, Dewasa maks. 41 tahun), MHQ Juz 30 (SD/MI), MKQ Khattil Qur'an (Umum), Murottal Tartil Anak. Pendaftaran dibuka 9 November – 24 Desember 2026 (WIB). MKQ tampil "segera dibuka" sampai biayanya diisi dan `open` diubah menjadi `true` di config.
- Data peserta: nama, NIK, tanggal lahir, jenis kelamin, alamat domisili (kecamatan Kota Malang), WhatsApp, asal & alamat sekolah/lembaga, satu cabang lomba. Satu NIK hanya dapat mengikuti satu cabang.
- Dokumen persyaratan: identitas diri (KTP/Kartu Keluarga/Kartu Pelajar/KIA, JPG/PNG/WEBP/PDF maks. 5 MB) dan pasfoto 3x4 (JPG/PNG/WEBP maks. 2 MB; hanya jenis dan ukuran file yang diperiksa) saat mendaftar; bukti transfer diunggah setelah mendaftar.
- Keamanan data: NIK dan semua dokumen dienkripsi dengan `APP_KEY`; jenis file dicek dari isinya; gambar ditulis ulang (metadata/GPS terhapus, sisipan kode ternetralisasi); dokumen hanya bisa dibuka panitia yang login; satu bukti transfer tidak bisa dipakai untuk dua pendaftaran; nama panitia yang menandai lunas tercatat.
- Panel panitia untuk mencari (termasuk berdasarkan nominal transfer, mis. `35.123`), memfilter status pembayaran, melihat bukti, menandai lunas atau menolak bukti dengan alasan, serta memverifikasi data peserta.
- Akun panitia dibuat melalui perintah Artisan; pendaftaran akun publik tidak tersedia.

Ketentuan mengikuti proposal dan Juknis Perlombaan MTQ Kota Malang 2026: lomba 26–27 Desember 2026 mulai 07.30 WIB di Malang Creative Center (MCC) Lt. 2, Blimbing; pengesahan peserta pada Technical Meeting 21 Desember 2026. Batas usia yang diperiksa aplikasi hanya Tilawah Dewasa (maks. 41 tahun pada hari lomba); jenjang sekolah diverifikasi panitia.

## Menjalankan aplikasi

1. Siapkan PHP 8.3+, Composer, Node.js, dan MySQL.
2. Buat basis data MySQL, misalnya `mtq_kota_malang`.
3. Salin `.env.example` menjadi `.env`, lalu atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai server Anda.
4. Jalankan:

   ```sh
   composer install
   php artisan key:generate
   php artisan migrate
   npm install
   npm run build
   ```

5. Buat akun panitia:

   ```sh
   php artisan mtq:make-admin
   ```

   Isi nama, email, dan kata sandi minimal 12 karakter saat diminta. Perintah ini juga dapat memperbarui akun panitia yang emailnya sama.

6. Jalankan server lokal:

   ```sh
   php artisan serve
   ```

Tes otomatis menggunakan SQLite in-memory:

```sh
php artisan test
```

## Production

Panduan memasang di VPS (Ubuntu) ada di [deploy/README.md](deploy/README.md).
