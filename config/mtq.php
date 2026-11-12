<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cabang, Kategori, dan Biaya Pendaftaran
    |--------------------------------------------------------------------------
    |
    | Biaya ditentukan per kategori. Setiap peserta juga mendapat kode unik
    | tiga digit (001–999) yang ditambahkan ke biaya agar panitia dapat
    | mencocokkan mutasi rekening dengan pendaftar.
    |
    | Cabang dengan 'open' => false tetap tampil di website sebagai
    | "segera diumumkan", tetapi belum bisa dipilih di formulir.
    |
    */

    'branches' => [
        'tilawah' => [
            'label' => 'MTQ (Tilawah Al-Qur’an)',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Tilawah Anak', 'description' => 'SD/MI s.d. kelas 8 SMP/MTs', 'fee' => 35000],
                'remaja' => ['label' => 'Tilawah Remaja', 'description' => 'Kelas 9 SMP/MTs s.d. kelas 12 SMA/MA', 'fee' => 50000],
                'dewasa' => ['label' => 'Tilawah Dewasa', 'description' => 'Mahasiswa s.d. usia maks. 41 tahun', 'fee' => 70000, 'max_age' => 41],
            ],
        ],
        'mhq' => [
            'label' => 'MHQ (Hifdzil Qur’an)',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Juz 30', 'description' => 'SD/MI', 'fee' => 35000],
            ],
        ],
        'khot_indah' => [
            'label' => 'MKQ (Khattil Qur’an)',
            // Buka setelah biaya pendaftaran MKQ diputuskan: isi 'fee' lalu ubah 'open' menjadi true.
            'open' => false,
            'categories' => [
                'umum' => ['label' => 'Umum', 'description' => 'Kaligrafi mushaf, khat Naskhi', 'fee' => null],
            ],
        ],
        'murottal' => [
            'label' => 'Murottal',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Tartil Anak', 'description' => 'SD/MI s.d. kelas 8 SMP/MTs', 'fee' => 35000],
            ],
        ],
    ],

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

    /** Hari pertama lomba; acuan perhitungan batas usia kategori. */
    'event_date' => '2026-12-26',

    /** Technical meeting H-5 sebelum lomba (26 Desember 2026). */
    'technical_meeting_date' => '2026-12-21',

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
