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

    // Pengaman login (SOP Capstone poin 1: mitigasi brute force).
    'login' => [
        // Salah sandi berturut-turut (per username + alamat IP) sebelum dikunci.
        'maks_percobaan' => (int) env('LOGIN_MAKS_PERCOBAAN', 5),
        // Lama kunci dalam detik.
        'durasi_kunci_detik' => (int) env('LOGIN_DURASI_KUNCI_DETIK', 60),
        // Batas total percobaan login per menit dari satu alamat IP (semua username).
        'maks_per_ip_per_menit' => (int) env('LOGIN_MAKS_PER_IP_PER_MENIT', 20),
    ],

    // Batas kategori usia (tahun, dihitung dari tanggal lahir). Disetujui 10 Okt 2026:
    // Balita < 5, Remaja 5–17, Dewasa 18–59, Lansia >= 60.
    'kategori_usia' => [
        'remaja_mulai' => 5,
        'dewasa_mulai' => 18,
        'lansia_mulai' => 60,
    ],

    // Data Warga.
    'warga' => [
        // Jumlah baris per halaman di daftar warga (SOP poin 5: wajib pagination).
        'per_halaman' => 15,
    ],

    // Kegiatan & Presensi (SKF-06).
    'kegiatan' => [
        // Kegiatan otomatis selesai setelah sekian jam sejak dibuat / dibuka kembali (revisi).
        'durasi_jam' => 24,
        // Jumlah kartu kegiatan per halaman.
        'per_halaman' => 9,
    ],
    'presensi' => [
        'per_halaman' => 15,
        // Jeda pembaruan otomatis halaman realtime, dalam detik (keputusan 23: polling 5 detik).
        'polling_detik' => (int) env('POLLING_DETIK', 5),
    ],

];
