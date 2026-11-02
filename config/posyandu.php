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
        // Alamat halaman login kader (tanpa garis miring), mis. "masuk-kader". Tidak ditautkan dari halaman publik.
        'path' => trim((string) env('LOGIN_PATH', 'login'), '/') ?: 'login',
    ],

    // Batas kategori usia (tahun, dihitung dari tanggal lahir).
    // Diperbarui setelah pengecekan bersama kader (31 Okt 2026): Balita < 6 (6 tahun kurang 1 hari),
    // Anak Usia Sekolah & Remaja 6–17 (18 tahun kurang 1 hari), Dewasa 18–59, Lansia >= 60.
    // Usia dihitung dalam tahun penuh, jadi "kurang 1 hari" sudah otomatis (5 th 364 hari = 5 tahun).
    'kategori_usia' => [
        'remaja_mulai' => 6,
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
        // Tekanan darah (pengecekan bersama kader, 31 Okt 2026). Angka "normal_sampai" = batas atas yang masih Normal:
        // - remaja: Tinggi bila sistolik > 130 (130 masih Normal) atau diastolik ≥ 85;
        // - dewasa (juga lansia, ibu hamil, ibu nifas): Tinggi bila sistolik ≥ 140 atau diastolik ≥ 90;
        // - semua kategori: Rendah bila sistolik < 90 atau diastolik < 70.
        // Rekapitulasi & Dashboard: Rendah dan Tinggi sama-sama dihitung Risiko.
        'tekanan_darah' => [
            'remaja' => ['sistolik_normal_sampai' => 130, 'diastolik_normal_sampai' => 84],
            'dewasa' => ['sistolik_normal_sampai' => 139, 'diastolik_normal_sampai' => 89],
            'rendah' => ['sistolik_di_bawah' => 90, 'diastolik_di_bawah' => 70],
        ],
        // Lingkar perut berisiko bila lebih dari batas (cm).
        'lingkar_perut' => ['perempuan' => 80, 'laki_laki' => 90],
        // Kadar HB normal bila ≥ 12 g/dL: remaja putri (tab Tahunan) dan lansia perempuan (tab Tahunan,
        // ditambahkan setelah pengecekan bersama kader 31 Okt 2026). Dewasa tidak diperiksa HB.
        'hb_normal_mulai' => 12,
        // Skrining TBC berisiko bila jumlah gejala "Ya" minimal sekian (disetujui: 2).
        'tbc_minimal_gejala' => 2,
        // Peringatan interval pemeriksaan (Activity Diagram 2.17 & 2.18), dalam bulan.
        'interval_bulan' => ['enam_bulanan' => 6, 'tahunan' => 12],
        // LILA: KEK bila LILA < 23,5 cm (otomatis dari angka). Dipakai ploting ibu hamil dan, setelah pengecekan
        // bersama kader (31 Okt 2026), juga status LILA dewasa & lansia (dihitung dari angka, tanpa kolom status).
        'lila_kek_di_bawah' => 23.5,
        // Gula Darah Sewaktu (GDS): berisiko bila >= 150 mg/dl; diperiksa dan WAJIB mulai usia 15 tahun
        // (remaja 15–17, dewasa, lansia), boleh diisi nakes atau kader berpengalaman (pengecekan bersama kader
        // 31 Okt 2026; sebelumnya >= 200). Status tidak disimpan di database; dihitung dari angka kadar_gula_darah.
        'gula_darah_berisiko' => 150,
        'gula_darah_usia_minimal' => 15,
        // AKS (Indeks Barthel skala 20, Kartu Bantu Pemeriksaan Lansia): total 20 = Mandiri, 12-19 Ringan,
        // 9-11 Sedang, 5-8 Berat, 0-4 Total; skor < 20 dirujuk ke Pustu/Puskesmas.
        'aks' => ['mandiri' => 20, 'ringan_mulai' => 12, 'sedang_mulai' => 9, 'berat_mulai' => 5],
        // Skrining PPOK PUMA (disetujui 16 Okt 2026): khusus usia >= 40; skor usia 40-49 = 0, 50-59 = 1, >= 60 = 2;
        // laki-laki = 1; berisiko PPOK bila total skor >= 6 (PDPI).
        'puma' => [
            'usia_minimal' => 40,
            'skor_usia' => [50 => 1, 60 => 2], // usia mulai => skor
            'berisiko_mulai' => 6,
        ],
    ],

    'vaksinasi' => [
        // Baris per halaman Daftar Vaksinasi Balita & Master Vaksin (SOP poin 5).
        'per_halaman' => 15,
        // ASI Eksklusif dicatat Bulan 0 - 6 seperti SIGIZI Kesga (disetujui 17 Okt 2026); bagian ASI hanya tampil
        // selama usia balita belum lewat 6 bulan (proposal Gambar 2.60).
        'asi_bulan_maks' => 6,
    ],

    'riwayat' => [
        // Jumlah baris per halaman di Daftar Warga Riwayat & Riwayat per Warga (SOP poin 5).
        'per_halaman' => 15,
    ],

    // Identitas posyandu untuk kop file Excel Rekapitulasi (formulir kertas Kemenkes).
    // Lokasi sesuai proposal: Tegalyoso, Banyuraden, Kecamatan Gamping, Kabupaten Sleman.
    'profil' => [
        'nama' => env('POSYANDU_NAMA', 'Posyandu Mawar 1'),
        'nama_tanpa_awalan' => env('POSYANDU_NAMA_SINGKAT', 'Mawar 1'),
        'dusun_rt_rw' => env('POSYANDU_DUSUN_RT_RW', 'Tegalyoso'),
        'desa' => env('POSYANDU_DESA', 'Banyuraden'),
        'kecamatan' => env('POSYANDU_KECAMATAN', 'Gamping'),
        // Halaman publik / profil posyandu (SKF-01, Figma 141:62). Teks tetap, diganti lewat .env (disetujui 21 Okt 2026).
        // Isian yang kosong tidak ditampilkan.
        'wilayah' => env('POSYANDU_WILAYAH', 'Tegalyoso, Banyuraden, Gamping, Sleman'),
        'deskripsi' => env('POSYANDU_DESKRIPSI', 'Posyandu Mawar 1 adalah pusat pelayanan kesehatan masyarakat yang rutin memantau kondisi balita, remaja, dewasa, lansia, hingga ibu hamil, meliputi pendataan warga, pemeriksaan dan screening kesehatan berkala, pengecekan vaksinasi dan ASI balita melalui Buku KIA, serta rekap data kesehatan bulanan.'),
        'jadwal' => env('POSYANDU_JADWAL', 'Dilaksanakan setiap bulan.'),
        'jam' => env('POSYANDU_JAM', ''),
        'alamat' => env('POSYANDU_ALAMAT', 'Tegalyoso, Banyuraden, Kecamatan Gamping, Kabupaten Sleman, DI Yogyakarta'),
        'narahubung' => env('POSYANDU_NARAHUBUNG', ''),
        'telepon' => env('POSYANDU_TELEPON', ''),
    ],

    // SOP Pengumpulan Capstone poin 4: batas ukuran isi kiriman & parameter alamat per request (KB).
    // Form terbesar ±5,7 KB (pemeriksaan lansia diisi penuh). Middleware BatasiUkuranRequest.
    'request' => [
        'maks_kb' => (int) env('REQUEST_MAKS_KB', 10),
    ],

    // Halaman publik: angka grafik disimpan sementara (detik) & batas permintaan per menit per alamat IP.
    'publik' => [
        'cache_detik' => (int) env('PUBLIK_CACHE_DETIK', 600),
        'maks_per_menit' => (int) env('PUBLIK_MAKS_PER_MENIT', 60),
    ],

    // Manajemen Pengguna / akun kader (SKF-11). Aturan disetujui 21 Okt 2026.
    'pengguna' => [
        'per_halaman' => 15,
        // Kata sandi: minimal 8, maksimal 64 karakter, tanpa paksaan huruf besar/angka/simbol (OWASP).
        'sandi_min' => 8,
        'sandi_maks' => 64,
        // Tolak sandi yang pernah bocor (layanan Have I Been Pwned; yang dikirim hanya 5 huruf awal hash,
        // bukan sandinya). Bila server tidak bisa menghubungi layanan, pengecekan ini dilewati.
        'cek_sandi_bocor' => (bool) env('PENGGUNA_CEK_SANDI_BOCOR', true),
        'cek_sandi_bocor_timeout_detik' => 5,
        // Sandi umum yang selalu ditolak walau server sedang tanpa internet (dibandingkan tanpa huruf besar/kecil).
        'sandi_umum' => [
            '12345678', '123456789', '1234567890', '87654321', '11111111', '00000000', '12341234',
            'password', 'password1', 'password123', 'qwerty123', 'qwertyuiop', 'abcd1234', 'iloveyou',
            'bismillah', 'indonesia', 'posyandu', 'posyandu1', 'posyandumawar', 'posyandumawar1',
            'mawar123', 'mawarsatu', 'kader123', 'kaderposyandu', 'admin123', 'administrator',
        ],
    ],

    'presensi' => [
        'per_halaman' => 15,
        // Jeda pembaruan otomatis halaman realtime, dalam detik (keputusan 23: polling 5 detik).
        'polling_detik' => (int) env('POLLING_DETIK', 5),
    ],

];
