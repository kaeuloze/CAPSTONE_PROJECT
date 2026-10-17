<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas SKRINING_TBC (ERD Gambar 2.2): skrining gejala TBC. Tidak dicentang = "Tidak".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skrining_tbc', function (Blueprint $table) {
            $table->increments('id_skrining_tbc');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->boolean('batuk_terus_menerus')->default(false);
            $table->boolean('demam_2_minggu')->default(false);
            $table->boolean('bb_tidak_stabil')->default(false);
            $table->boolean('kontak_pasien_tbc')->default(false);
            $table->enum('status_gejala_tbc', ['normal', 'berisiko'])->default('normal');
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skrining_tbc');
    }
};
