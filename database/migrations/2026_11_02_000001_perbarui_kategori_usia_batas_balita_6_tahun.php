<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Batas Balita diubah dari < 5 tahun menjadi < 6 tahun (pengecekan bersama kader, 31 Okt 2026).
 * Warga berusia 5 tahun yang sudah tercatat Remaja langsung dikembalikan ke Balita (tanpa menunggu
 * jadwal harian warga:perbarui-kategori). Pemeriksaan lama tidak diubah: kategori saat diperiksa
 * tetap mengikuti aturan pada tanggal pemeriksaan itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('warga:perbarui-kategori');
    }

    /** Tidak dibatalkan: kategori selalu bisa dihitung ulang dari tanggal lahir dengan perintah yang sama. */
    public function down(): void
    {
    }
};
