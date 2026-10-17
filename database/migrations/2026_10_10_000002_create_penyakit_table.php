<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas PENYAKIT (ERD Gambar 2.2): master Penyakit Tidak Menular (PTM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyakit', function (Blueprint $table) {
            $table->increments('id_penyakit');
            $table->string('nama_penyakit', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyakit');
    }
};
