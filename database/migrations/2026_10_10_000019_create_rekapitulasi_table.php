<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas REKAPITULASI (ERD Gambar 2.2): ringkasan laporan per kategori & periode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekapitulasi', function (Blueprint $table) {
            $table->increments('id_rekapitulasi');
            $table->enum('kategori_rekap', ['remaja', 'ibu_hamil_nifas', 'dewasa_lansia']);
            $table->unsignedTinyInteger('periode_bulan');  // 1 - 12
            $table->unsignedSmallInteger('periode_tahun');
            $table->date('tanggal_dibuat');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['kategori_rekap', 'periode_bulan', 'periode_tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekapitulasi');
    }
};
