<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas PEMERIKSAAN_BUMIL (ERD Gambar 2.2): pemeriksaan khusus ibu hamil / nifas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan_bumil', function (Blueprint $table) {
            $table->increments('id_bumil');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->enum('jenis_ibu', ['hamil', 'nifas']);
            $table->string('usia_kehamilan_minggu', 20)->nullable(); // rentang dari dropdown, mis. "12-16"
            $table->enum('status_ploting_imt', ['kurus', 'normal', 'gemuk', 'obesitas'])->nullable();
            $table->enum('status_ploting_lila', ['normal', 'kek'])->nullable();
            $table->enum('status_ploting_tekanan_darah', ['rendah', 'normal', 'tinggi'])->nullable();
            $table->unsignedInteger('jumlah_ttd_mms')->nullable();
            $table->boolean('status_konsumsi_ttd_rutin')->nullable();
            $table->text('komposisi_mt_bumil_kek')->nullable();
            $table->boolean('status_rutin_mt_kek')->nullable();
            $table->boolean('ikut_kelas_ibu_hamil')->nullable();
            $table->unsignedInteger('jumlah_kapsul_vit_a')->nullable();
            $table->boolean('status_rutin_vit_a')->nullable();
            $table->boolean('status_menyusui')->nullable();
            $table->boolean('status_kb_pascasalin')->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_bumil');
    }
};
