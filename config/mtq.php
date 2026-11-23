<?php

return [

    /*
    | Cabang, kategori, dan biaya pendaftaran dikelola panitia melalui menu
    | "Cabang & biaya" di panel panitia (tabel competition_branches dan
    | competition_categories). Setiap peserta juga mendapat kode unik tiga digit
    | (001–999) yang ditambahkan ke biaya agar mutasi rekening mudah dicocokkan.
    */

    /*
    |--------------------------------------------------------------------------
    | Jadwal
    |--------------------------------------------------------------------------
    |
    | Waktu mengikuti zona waktu aplikasi (WIB). Di luar rentang ini formulir
    | pendaftaran ditutup; peserta yang sudah terdaftar tetap bisa mengunggah
    | bukti pembayaran.
    |
    */

    'registration_opens_at' => '2026-11-09 00:00:00',
    'registration_closes_at' => '2026-12-24 23:59:59',

    /*
    | Jadwal bawaan Technical Meeting dan pelaksanaan lomba. Panitia dapat mengubahnya
    | di panel admin (tab Juknis); nilai dari panel admin menggantikan nilai di bawah ini.
    */

    /** Hari pertama lomba; acuan perhitungan batas usia kategori. */
    'event_date' => '2026-12-26',
    'event_end_date' => '2026-12-27',
    'event_time' => '07.30 WIB s.d. selesai',
    'event_place' => 'Malang Creative Center (MCC) Lt. 2',

    /** Technical meeting H-5 sebelum lomba (26 Desember 2026). */
    'technical_meeting_date' => '2026-12-21',
    'technical_meeting_note' => 'ambil nomor urut & maqra’',

    'bank' => [
        'name' => 'Bank Mandiri',
        'account_name' => 'AL IHSAN/USMAN',
        'account_number' => '144-00-3024646-3',
    ],

    'contacts' => [
        ['name' => 'Ustadz Al Ihsan', 'phone' => '083114646228'],
        ['name' => 'Ustadzah Sarfina', 'phone' => '085179928851'],
        ['name' => 'H. Erik Agus Wilyanto', 'phone' => '081334997157'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun Admin Awal
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh AdminUserSeeder (php artisan db:seed). Nilai bawaan dapat
    | diganti lewat MTQ_ADMIN_EMAIL dan MTQ_ADMIN_PASSWORD di .env.
    |
    */

    'admin' => [
        'name' => env('MTQ_ADMIN_NAME', 'Admin MTQ 2026'),
        'email' => env('MTQ_ADMIN_EMAIL', 'adminmtq2026@gmail.com'),
        'password' => env('MTQ_ADMIN_PASSWORD', 'punyaadminmtq2026'),
    ],

    'address' => 'Masjid Jami’ Blimbing, Jl. Ahmad Yani No.55, Blimbing, Kota Malang 65126',

];
