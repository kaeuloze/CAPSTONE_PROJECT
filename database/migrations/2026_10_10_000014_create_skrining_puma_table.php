<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas SKRINING_PUMA (ERD Gambar 2.2): skrining PPOK (usia > 40 tahun, tab tahunan).
 * Tambahan (disetujui): gejala_dahak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skrining_puma', function (Blueprint $table) {
            $table->increments('id_puma');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->unsignedTinyInteger('skor_jenis_kelamin')->nullable();
            $table->unsignedTinyInteger('skor_usia')->nullable();
            $table->unsignedTinyInteger('skor_merokok')->nullable();
            $table->boolean('gejala_napas_pendek')->default(false);
            $table->boolean('gejala_dahak')->default(false);
            $table->boolean('gejala_batuk_tanpa_flu')->default(false);
            $table->boolean('pernah_spirometri')->default(false);
            $table->unsignedTinyInteger('skor_puma_total')->nullable();
            $table->enum('status_ppok', ['tidak_berisiko', 'berisiko'])->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skrining_puma');
    }
};
