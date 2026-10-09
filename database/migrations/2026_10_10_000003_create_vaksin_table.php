<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas VAKSIN (ERD Gambar 2.2): master jenis vaksin.
 * Setiap dosis dicatat sebagai vaksin tersendiri (mis. "Polio 1", "Polio 2").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaksin', function (Blueprint $table) {
            $table->increments('id_vaksin');
            $table->string('nama_vaksin', 100)->unique();
            $table->string('deskripsi', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaksin');
    }
};
