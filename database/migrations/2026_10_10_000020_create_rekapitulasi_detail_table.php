<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas REKAPITULASI_DETAIL (ERD Gambar 2.2): baris-baris item rekap (format Kemenkes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekapitulasi_detail', function (Blueprint $table) {
            $table->increments('id_rekap_detail');
            $table->unsignedInteger('id_rekapitulasi');
            $table->string('kode_item', 50);
            $table->string('nama_item', 255);
            $table->integer('nilai')->default(0);
            $table->unsignedInteger('urutan');
            $table->timestamps();

            $table->unique(['id_rekapitulasi', 'kode_item']);
            $table->foreign('id_rekapitulasi')->references('id_rekapitulasi')->on('rekapitulasi')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekapitulasi_detail');
    }
};
