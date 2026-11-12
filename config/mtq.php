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
            'label' => 'Tilawah Al-Qur’an',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Anak', 'description' => 'SD s.d. SMP kelas 8', 'fee' => 35000],
                'remaja' => ['label' => 'Remaja', 'description' => 'SMP kelas 9 s.d. SMA kelas 12', 'fee' => 50000],
                'dewasa' => ['label' => 'Dewasa', 'description' => 'Mahasiswa s.d. usia lanjut', 'fee' => 70000],
            ],
        ],
        'mhq' => [
            'label' => 'MHQ Juz 30',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Anak', 'description' => 'Tingkat SD', 'fee' => 35000],
            ],
        ],
        'pildacil' => [
            'label' => 'Pildacil (Da’i Cilik)',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Anak', 'description' => 'Tingkat SD', 'fee' => 35000],
            ],
        ],
        'murottal' => [
            'label' => 'Murottal',
            'open' => true,
            'categories' => [
                'anak-anak' => ['label' => 'Anak', 'description' => 'Tingkat SD', 'fee' => 35000],
            ],
        ],
        'khot_indah' => [
            'label' => 'Khot Indah Al-Qur’an',
            'open' => false,
            'categories' => [],
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

    'registration_opens_at' => '2026-11-07 00:00:00',
    'registration_closes_at' => '2026-12-24 23:59:59',

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

    'address' => 'Masjid Jami’ Blimbing, Jl. Ahmad Yani No.55, Blimbing, Kota Malang 65126',

];
