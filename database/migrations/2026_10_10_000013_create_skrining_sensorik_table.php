<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas SKRINING_SENSORIK (ERD Gambar 2.2): tes penglihatan & pendengaran (tab 6 bulan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skrining_sensorik', function (Blueprint $table) {
            $table->increments('id_skrining_sensorik');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->string('mata_kanan', 20)->nullable();
            $table->string('mata_kiri', 20)->nullable();
            $table->string('telinga_kanan', 20)->nullable();
            $table->string('telinga_kiri', 20)->nullable();
            $table->enum('status_penglihatan', ['normal', 'gangguan'])->nullable();
            $table->enum('status_pendengaran', ['normal', 'gangguan'])->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skrining_sensorik');
    }
};
