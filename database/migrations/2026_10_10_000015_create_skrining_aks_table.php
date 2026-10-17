<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas SKRINING_AKS (ERD Gambar 2.2): Aktivitas Kehidupan Sehari-hari lansia (tab tahunan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skrining_aks', function (Blueprint $table) {
            $table->increments('id_aks');
            $table->unsignedInteger('id_pemeriksaan')->unique();
            foreach ([
                'skor_bab', 'skor_bak', 'skor_kebersihan_diri', 'skor_penggunaan_wc', 'skor_makan_minum',
                'skor_transfer_kursi', 'skor_berjalan', 'skor_berpakaian', 'skor_naik_tangga', 'skor_mandi',
            ] as $kolom) {
                $table->unsignedTinyInteger($kolom)->nullable();
            }
            $table->unsignedTinyInteger('skor_total')->nullable();
            $table->enum('status_aks', ['mandiri', 'ringan', 'sedang', 'berat', 'total'])->nullable();
            $table->timestamps();

            $table->foreign('id_pemeriksaan')->references('id_pemeriksaan')->on('pemeriksaan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skrining_aks');
    }
};
