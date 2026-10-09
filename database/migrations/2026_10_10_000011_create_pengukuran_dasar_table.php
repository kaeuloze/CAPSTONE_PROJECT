<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas PENGUKURAN_DASAR (ERD Gambar 2.2): hasil penimbangan & pengukuran (tab Rutin).
 * Semua isian boleh kosong agar tombol Simpan (draft) bisa dipakai; isian wajib dicek saat Submit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengukuran_dasar', function (Blueprint $table) {
            $table->increments('id_pengukuran');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->decimal('berat_badan', 5, 2)->nullable();      // kg
            $table->decimal('tinggi_badan', 5, 2)->nullable();     // cm
            $table->decimal('lingkar_perut', 5, 2)->nullable();    // cm
            $table->decimal('lila', 4, 1)->nullable();             // cm
            $table->string('tekanan_darah', 7)->nullable();        // "120/80" (gabungan sistolik/diastolik)
            $table->decimal('kadar_gula_darah', 5, 1)->nullable(); // mg/dl, diisi Nakes
            $table->decimal('kadar_hb', 4, 1)->nullable();         // g/dL
            $table->enum('status_imt', ['kurus', 'normal', 'gemuk', 'obesitas'])->nullable();
            $table->enum('status_tekanan_darah', ['rendah', 'normal', 'tinggi'])->nullable();
            $table->enum('status_lingkar_perut', ['normal', 'berisiko'])->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengukuran_dasar');
    }
};
