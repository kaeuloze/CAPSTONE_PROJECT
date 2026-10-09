<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Aplikasi Posyandu Mawar 1
|--------------------------------------------------------------------------
| Semua nilai sensitif (username/password) dibaca dari file .env,
| tidak ditulis langsung di kode (SOP Capstone poin 3 & 4).
*/

return [

    // Akun kader pertama yang dibuat oleh seeder (KaderSeeder).
    'kader_awal' => [
        'username' => env('KADER_AWAL_USERNAME'),
        'password' => env('KADER_AWAL_PASSWORD'),
        'nama' => env('KADER_AWAL_NAMA', 'Kader Posyandu Mawar 1'),
    ],

];
