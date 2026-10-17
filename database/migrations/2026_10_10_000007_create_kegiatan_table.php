<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas KEGIATAN (ERD Gambar 2.2): setiap pelaksanaan posyandu.
 * Tambahan (disetujui, revisi aturan 24 jam): waktu_dibuka.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->increments('id_kegiatan');
            $table->string('nama_kegiatan', 100);
            $table->date('tanggal_kegiatan');
            $table->boolean('status_kegiatan')->default(true); // 1 = Berlangsung, 0 = Selesai
            $table->dateTime('waktu_dibuka'); // awal hitungan 24 jam (saat dibuat / dibuka kembali)
            $table->timestamps();

            $table->index(['status_kegiatan', 'tanggal_kegiatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
