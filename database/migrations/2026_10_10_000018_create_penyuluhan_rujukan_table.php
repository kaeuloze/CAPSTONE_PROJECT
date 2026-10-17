<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas PENYULUHAN_RUJUKAN (ERD Gambar 2.2): penyuluhan & rujukan pada setiap pemeriksaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyuluhan_rujukan', function (Blueprint $table) {
            $table->increments('id_penyuluhan');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            $table->string('topik_penyuluhan', 255)->nullable();
            $table->enum('status_rujukan', ['tidak_dirujuk', 'rujuk_puskesmas'])->default('tidak_dirujuk');
            $table->date('tanggal_rujukan')->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyuluhan_rujukan');
    }
};
