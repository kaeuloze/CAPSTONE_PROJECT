<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas ASI_EKSKLUSIF (ERD Gambar 2.2): pemantauan ASI eksklusif bulan ke-1 s.d. 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asi_eksklusif', function (Blueprint $table) {
            $table->increments('id_asi');
            $table->string('nik', 16);
            $table->unsignedTinyInteger('bulan_ke'); // 1 - 6
            $table->enum('status_asi', ['ya', 'tidak', 'tidak_recall']);
            $table->date('tanggal_dicatat');
            $table->timestamps();

            $table->unique(['nik', 'bulan_ke']);
            $table->foreign('nik')->references('nik')->on('warga')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asi_eksklusif');
    }
};
