<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tabel REKAPITULASI[cite: 19]
        Schema::create('rekapitulasi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->timestamps();
        });

        // Tabel REKAPITULASI_DETAIL[cite: 19]
        Schema::create('rekapitulasi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekapitulasi_id')->constrained('rekapitulasi')->onDelete('cascade');
            $table->string('item');
            $table->text('nilai');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rekapitulasi_detail');
        Schema::dropIfExists('rekapitulasi');
    }
};