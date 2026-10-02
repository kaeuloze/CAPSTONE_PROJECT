<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16);
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->onDelete('cascade');
            $table->string('kategori_usia_saat_diperiksa');
            $table->string('jenis_pemeriksaan');
            $table->date('tanggal_pemeriksaan');
            $table->timestamps();

            $table->foreign('nik')->references('nik')->on('warga')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('pemeriksaan');
    }
};