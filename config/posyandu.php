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
        // Jumlah baris tabel Arsip Kegiatan per halaman.
        'per_halaman' => 15,
    ],
    // Batas status otomatis hasil pemeriksaan (keputusan 5), mengikuti format resmi Rekapitulasi (Figma 306:103).
    'ambang' => [
        // IMT: Kurus < 18,5 ≤ Normal < 25,0 ≤ Gemuk ≤ 27,0 < Obesitas (rekap: Normal 18,5–24,9 / Risiko).
        'imt' => ['normal_mulai' => 18.5, 'gemuk_mulai' => 25.0, 'gemuk_sampai' => 27.0],
        // Tekanan darah Tinggi bila sistolik ≥ 130 atau diastolik ≥ 85 (rekap: Normal < 130/85).
        'tekanan_darah' => ['sistolik_tinggi' => 130, 'diastolik_tinggi' => 85],
        // Lingkar perut berisiko bila lebih dari batas (cm).
        'lingkar_perut' => ['perempuan' => 80, 'laki_laki' => 90],
        // Kadar HB remaja putri normal bila ≥ 12 g/dL.
        'hb_remaja_putri' => 12,
        // Skrining TBC berisiko bila jumlah gejala "Ya" minimal sekian (disetujui: 2).
        'tbc_minimal_gejala' => 2,
        // Peringatan interval pemeriksaan (Activity Diagram 2.17 & 2.18), dalam bulan.
        'interval_bulan' => ['enam_bulanan' => 6, 'tahunan' => 12],
    ],

    'riwayat' => [
        // Jumlah baris per halaman di Daftar Warga Riwayat & Riwayat per Warga (SOP poin 5).
        'per_halaman' => 15,
    ],

    'presensi' => [
        'per_halaman' => 15,
        // Jeda pembaruan otomatis halaman realtime, dalam detik (keputusan 23: polling 5 detik).
        'polling_detik' => (int) env('POLLING_DETIK', 5),
    ],

];
