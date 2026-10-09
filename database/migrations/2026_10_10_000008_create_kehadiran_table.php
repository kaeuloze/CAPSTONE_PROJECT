<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas KEHADIRAN (ERD Gambar 2.2): presensi warga pada kegiatan.
 * Ketidaksesuaian ERD (disetujui): waktu_kehadiran DATETIME (ERD: varchar), NIK VARCHAR(16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kehadiran', function (Blueprint $table) {
            $table->increments('id_kehadiran');
            $table->string('nik', 16);
            $table->unsignedInteger('id_kegiatan');
            $table->boolean('status_kehadiran')->default(false); // 1 = Hadir, 0 = Tidak/Belum hadir
            $table->dateTime('waktu_kehadiran')->nullable();
            $table->timestamps();

            $table->unique(['nik', 'id_kegiatan']); // mencegah presensi ganda
            $table->foreign('nik')->references('nik')->on('warga')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_kegiatan')->references('id_kegiatan')->on('kegiatan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kehadiran');
    }
};
