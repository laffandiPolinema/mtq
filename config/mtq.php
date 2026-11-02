<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Biaya Pendaftaran
    |--------------------------------------------------------------------------
    |
    | Nominal dasar pendaftaran. Setiap peserta mendapat kode unik tiga digit
    | (001–999) yang ditambahkan ke nominal ini agar panitia dapat mencocokkan
    | mutasi rekening dengan pendaftar.
    |
    */

    'registration_fee' => (int) env('MTQ_REGISTRATION_FEE', 75000),

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
