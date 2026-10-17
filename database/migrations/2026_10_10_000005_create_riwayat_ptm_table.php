<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas RIWAYAT_PTM (ERD Gambar 2.2): riwayat penyakit tidak menular warga.
 * Ketidaksesuaian ERD (disetujui): kolom "tanggal dicatat" ditulis tanggal_dicatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_ptm', function (Blueprint $table) {
            $table->increments('id_riwayat');
            $table->string('nik', 16);
            $table->unsignedInteger('id_penyakit');
            $table->enum('sumber_riwayat', ['kartu_bantu', 'wawancara', 'fasilitas_kesehatan'])->default('kartu_bantu');
            $table->date('tanggal_dicatat');
            $table->timestamps();

            $table->unique(['nik', 'id_penyakit']);
            $table->foreign('nik')->references('nik')->on('warga')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_penyakit')->references('id_penyakit')->on('penyakit')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_ptm');
    }
};
