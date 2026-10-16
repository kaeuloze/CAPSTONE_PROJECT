<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas VAKSINASI (ERD Gambar 2.2): catatan vaksin (per dosis) yang diterima warga.
 * Ketidaksesuaian ERD (disetujui): kolom jenis_vaksinasi dihapus (dosis dibedakan lewat master VAKSIN);
 * id_kegiatan boleh kosong untuk vaksin lama yang disalin dari buku KIA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaksinasi', function (Blueprint $table) {
            $table->increments('id_vaksinasi');
            $table->string('nik', 16);
            $table->unsignedInteger('id_vaksin');
            $table->unsignedInteger('id_kegiatan')->nullable();
            $table->boolean('status_vaksinasi')->default(false); // 1 = Sudah
            $table->date('tanggal_vaksinasi')->nullable();
            $table->timestamps();

            $table->unique(['nik', 'id_vaksin']); // satu dosis dicatat sekali
            $table->foreign('nik')->references('nik')->on('warga')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_vaksin')->references('id_vaksin')->on('vaksin')->restrictOnDelete();
            $table->foreign('id_kegiatan')->references('id_kegiatan')->on('kegiatan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaksinasi');
    }
};
