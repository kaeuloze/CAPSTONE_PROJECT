<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas SKRINING_SKILAS (ERD Gambar 2.2): Skrining Lansia Sederhana (tab tahunan). Tidak dicentang = "Tidak".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skrining_skilas', function (Blueprint $table) {
            $table->increments('id_skilas');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            foreach ([
                'orientasi_waktu_tempat', 'mengulang_tiga_kata', 'tes_berdiri_kursi', 'bb_berkurang',
                'hilang_nafsu_makan', 'lila_kurang', 'masalah_penglihatan', 'tes_melihat', 'tes_berbisik',
                'perasaan_sedih', 'kurang_minat', 'imunisasi_covid',
            ] as $kolom) {
                $table->boolean($kolom)->default(false);
            }
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skrining_skilas');
    }
};
