<?php
/**
 * DATA DUMMY - Capstone Project 2 (Frontend)
 * ---------------------------------------------------------------
 * File ini adalah SATU-SATUNYA sumber data dummy.
 * Nanti saat backend sudah jadi, array di bawah diganti dengan
 * data dari database. Struktur markup Blade tidak berubah.
 *
 * @var array $ref
 */

return [
    // ---------- DATA POSYANDU (SKF-01) ----------
    'posyandu' => [
        'nama'      => 'Posyandu Mawar 1',
        'lokasi'    => 'Tegalyoso, Banyuraden',
        'kecamatan' => 'Kecamatan Gamping',
        'kabupaten' => 'Kabupaten Sleman, DIY',
        'alamat'    => 'Tegalyoso, Banyuraden, Kec. Gamping, Kab. Sleman, DIY',
        'telepon'   => '0812-3456-7890',
        'jam'       => '08.00 - 12.00 WIB',

        'deskripsi' => 'Pusat pelayanan kesehatan masyarakat yang rutin memantau kondisi balita, remaja, dewasa, hingga lanjut usia dan ibu hamil.',

        'ringkasan' => 'Posyandu Mawar 1 merupakan pusat pelayanan kesehatan masyarakat yang secara rutin memantau kondisi balita, remaja, dewasa, hingga lanjut usia dan ibu hamil. Kegiatan yang dilakukan meliputi pendataan warga, pencatatan hasil pemeriksaan, screening kesehatan berkala, pengecekan riwayat vaksinasi dan pantauan ASI balita melalui buku KIA, serta perekapan data kesehatan bulanan warga. Seluruh data tersebut diolah menjadi grafik agar perkembangan kesehatan warga mudah dipantau.',

        'kegiatan' => [
            'Pendataan Warga',
            'Pencatatan Hasil Pemeriksaan',
            'Screening Kesehatan Berkala',
            'Pengecekan Riwayat Vaksinasi',
            'Pemantauan ASI Balita (Buku KIA)',
            'Rekapitulasi Data Bulanan',
        ],
    ],

    // ---------- KATEGORI USIA (SKF-05) ----------
    // key = nilai yang disimpan di DB (warga.kategori_usia)
    'kategori' => [
        'balita' => ['label' => 'Balita',            'rentang' => '0 - 5 tahun',    'warna' => 'amber'],
        'remaja' => ['label' => 'Remaja',            'rentang' => '6 - 18 tahun',   'warna' => 'sky'],
        'dewasa' => ['label' => 'Dewasa',            'rentang' => '18 - 59 tahun',  'warna' => 'emerald'],
        'lansia' => ['label' => 'Lansia',            'rentang' => '≥ 60 tahun',     'warna' => 'violet'],
        'bumil'  => ['label' => 'Ibu Hamil/Nifas',   'rentang' => 'Kondisi khusus', 'warna' => 'rose'],
    ],

    // ---------- STATUS KEHADIRAN (SKF-06) ----------
    'kehadiran' => [
        'hadir' => ['label' => 'Hadir',       'warna' => 'emerald'],
        'belum' => ['label' => 'Belum Hadir', 'warna' => 'slate'],
        'tidak' => ['label' => 'Tidak Hadir', 'warna' => 'rose'],
    ],

    // ---------- STATUS KEGIATAN ----------
    'kegiatan' => [
        'berlangsung' => ['label' => 'Berlangsung', 'warna' => 'emerald'],
        'selesai'     => ['label' => 'Selesai',     'warna' => 'slate'],
    ],

    // ---------- FORM PARAMETER PEMERIKSAAN ----------
    // dipakai untuk render form generik per kategori
    'parameter_dasar' => [
        'berat_badan'      => ['label' => 'Berat Badan',   'satuan' => 'kg',    'wajib' => true,  'step' => '0.1'],
        'tinggi_badan'     => ['label' => 'Tinggi Badan',  'satuan' => 'cm',    'wajib' => true,  'step' => '0.1'],
        'lingkar_perut'    => ['label' => 'Lingkar Perut', 'satuan' => 'cm',    'wajib' => false, 'step' => '0.1'],
        'lila'             => ['label' => 'LILA',          'satuan' => 'cm',    'wajib' => false, 'step' => '0.1'],
        'tekanan_darah'    => ['label' => 'Tekanan Darah', 'satuan' => 'mmHg',  'wajib' => false, 'placeholder' => '120/80'],
        'kadar_gula_darah' => ['label' => 'Gula Darah',    'satuan' => 'mg/dL', 'wajib' => false, 'step' => '0.1'],
        'kadar_hb'         => ['label' => 'Kadar HB',      'satuan' => 'g/dL',  'wajib' => false, 'step' => '0.1'],
    ],

    // ---------- SKRINING GEJALA TBC (skrining_tbc) ----------
    'tbc' => [
        'batuk_terus_menerus'       => 'Batuk terus menerus ≥ 2 minggu',
        'demam_berkepanjangan'      => 'Demam berkepanjangan',
        'riwayat_kontak_pasien_tbc' => 'Pernah berkontak dengan pasien TBC',
    ],

    // ---------- STATUS KESEHATAN ----------
    'status_imt'            => ['Kurus', 'Normal', 'Gemuk', 'Obesitas'],
    'status_td'             => ['Normal', 'Hipertensi', 'Hipertensi Berat'],
    'status_lingkar_perut'  => ['Normal', 'Normal Tinggi', 'Obesitas Sentral'],

    // ---------- VAKSIN (vaksin) ----------
    'vaksin' => [
        ['nama' => 'HB-0',           'deskripsi' => 'Vaksin Hepatitis B dosis 0, diberikan pada < 24 jam'],
        ['nama' => 'BCG',            'deskripsi' => 'Bacillus Calmette Guerin untuk mencegah tuberculosis'],
        ['nama' => 'Polio 0',        'deskripsi' => 'Vaksin Polio Oral dosis 0'],
        ['nama' => 'DPT-HB-Hib 1',   'deskripsi' => 'Dosis pertama, diberikan pada usia 2 bulan'],
        ['nama' => 'Polio 1',        'deskripsi' => 'Vaksin Polio Oral dosis 1'],
        ['nama' => 'Campak-Rubella', 'deskripsi' => 'Vaksin campak dan rubella, diberikan pada usia 9 bulan'],
    ],

    // ---------- DAFTAR WARGA DUMMY (warga) ----------
    'warga' => [
        ['nik' => '3401124502100001', 'nama' => 'Siti Rahmawati', 'jk' => 'P', 'tanggal_lahir' => '1990-02-15', 'kategori' => 'dewasa', 'status_kondisi' => 'Normal', 'alamat' => 'Tegalyoso RT 02'],
        ['nik' => '3401121203150002', 'nama' => 'Budi Santoso',   'jk' => 'L', 'tanggal_lahir' => '2015-03-20', 'kategori' => 'remaja', 'status_kondisi' => 'Normal', 'alamat' => 'Tegalyoso RT 02'],
        ['nik' => '3401125507100003', 'nama' => 'Ngatinem',       'jk' => 'P', 'tanggal_lahir' => '1955-07-18', 'kategori' => 'lansia', 'status_kondisi' => 'Normal', 'alamat' => 'Tegalyoso RT 01'],
        ['nik' => '3401126705050004', 'nama' => 'Dewi Lestari',   'jk' => 'P', 'tanggal_lahir' => '2024-05-09', 'kategori' => 'balita', 'status_kondisi' => 'Normal', 'alamat' => 'Tegalyoso RT 03'],
        ['nik' => '3401124301250005', 'nama' => 'Rina Marlina',   'jk' => 'P', 'tanggal_lahir' => '1998-01-30', 'kategori' => 'dewasa', 'status_kondisi' => 'Hamil',  'alamat' => 'Tegalyoso RT 03'],
        ['nik' => '3401121111800006', 'nama' => 'Joko Susilo',    'jk' => 'L', 'tanggal_lahir' => '1980-11-11', 'kategori' => 'dewasa', 'status_kondisi' => 'Normal', 'alamat' => 'Tegalyoso RT 04'],
    ],

    // ---------- STATISTIK DASHBOARD (SKF-03) ----------
    'statistik' => [
        'warga_diperiksa' => 47,
        'perlu_rujukan'   => 5,
        'gizi_normal'     => 31,
        'bergejala_tbc'   => 2,
    ],
        // ---------- DAFTAR KADER DUMMY (kader) ----------
    'kader' => [
        ['username' => 'kader', 'nama_kader' => 'Kader Posyandu Mawar 1', 'created_at' => '12 Oktober 2026', 'sandi_diisi' => true],
        ['username' => 'yuli',  'nama_kader' => 'Ibu Yuli',                'created_at' => '12 Oktober 2026', 'sandi_diisi' => true],
        ['username' => 'sri',   'nama_kader' => 'Ibu Sri Wahyuni',         'created_at' => '13 Oktober 2026', 'sandi_diisi' => true],
        ['username' => 'umi',   'nama_kader' => 'Ibu Umi Astuti',          'created_at' => '13 Oktober 2026', 'sandi_diisi' => true],
        ['username' => 'maura', 'nama_kader' => 'Maura Anindita Kirana',   'created_at' => '14 Oktober 2026', 'sandi_diisi' => false],
    ],
];