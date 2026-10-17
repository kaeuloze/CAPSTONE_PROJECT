<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas WARGA (ERD Gambar 2.2): data induk warga sasaran posyandu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warga', function (Blueprint $table) {
            $table->string('nik', 16)->primary();
            $table->string('nama_warga', 100);
            $table->boolean('jenis_kelamin'); // 1 = Laki-laki, 0 = Perempuan
            $table->date('tanggal_lahir');
            $table->string('no_hp', 15)->nullable(); // opsional (wireframe Tambah Warga)
            $table->string('alamat', 255);
            $table->string('status_kondisi', 20)->default('normal'); // normal | hamil | nifas | menyusui (manual)
            $table->string('kategori_usia', 20); // balita | remaja | dewasa | lansia (otomatis dari tanggal lahir)
            $table->timestamps();

            $table->index('kategori_usia');
            $table->index('nama_warga');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga');
    }
};
