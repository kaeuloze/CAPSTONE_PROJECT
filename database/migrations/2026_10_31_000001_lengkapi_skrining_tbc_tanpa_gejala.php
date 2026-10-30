<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Melengkapi data lama: pemeriksaan selesai yang melakukan pemeriksaan Rutin tetapi semua gejala TBC-nya "Tidak"
 * belum punya baris skrining_tbc (bagian kosong tidak tersimpan). Baris "Normal, 0 gejala" ditambahkan agar warga
 * tersebut terhitung "Tidak Bergejala" di grafik Skrining TBC Dashboard & Profil Publik.
 * Pemeriksaan baru sudah tercatat otomatis saat Submit (Bagian::$catatBilaKosong).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            INSERT INTO skrining_tbc (id_pemeriksaan, batuk_terus_menerus, demam_2_minggu, bb_tidak_stabil,
                                      kontak_pasien_tbc, status_gejala_tbc, created_at, updated_at)
            SELECT p.id_pemeriksaan, 0, 0, 0, 0, 'normal', NOW(), NOW()
            FROM pemeriksaan p
            LEFT JOIN skrining_tbc t ON t.id_pemeriksaan = p.id_pemeriksaan
            WHERE t.id_pemeriksaan IS NULL
              AND p.status_pemeriksaan = 'selesai'
              AND FIND_IN_SET('rutin', p.jenis_pemeriksaan) > 0
        ");
    }

    /** Tidak dibatalkan: baris yang ditambahkan tidak bisa dibedakan dari isian kader yang semua gejalanya "Tidak". */
    public function down(): void
    {
    }
};
