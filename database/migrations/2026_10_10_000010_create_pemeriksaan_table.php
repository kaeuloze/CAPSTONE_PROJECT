<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas PEMERIKSAAN (ERD Gambar 2.2): satu kunjungan pemeriksaan warga pada satu kegiatan.
 * Ketidaksesuaian ERD (disetujui): NIK VARCHAR(16) (ERD: int); jenis_pemeriksaan SET (ERD: enum);
 * tambahan status_pemeriksaan (draft = Simpan, selesai = Submit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeriksaan', function (Blueprint $table) {
            $table->increments('id_pemeriksaan');
            $table->string('nik', 16);
            $table->unsignedInteger('id_kegiatan');
            $table->enum('kategori_usia_saat_periksa', ['remaja', 'dewasa', 'lansia', 'ibu_hamil', 'ibu_nifas']);
            $table->set('jenis_pemeriksaan', ['rutin', 'enam_bulanan', 'tahunan'])->nullable(); // diisi otomatis saat Submit
            $table->date('tanggal_pemeriksaan');
            $table->enum('status_pemeriksaan', ['draft', 'selesai'])->default('draft');
            $table->timestamps();

            $table->unique(['nik', 'id_kegiatan']); // satu kunjungan = satu pemeriksaan
            $table->index(['status_pemeriksaan', 'tanggal_pemeriksaan']);
            $table->foreign('nik')->references('nik')->on('warga')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_kegiatan')->references('id_kegiatan')->on('kegiatan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan');
    }
};
