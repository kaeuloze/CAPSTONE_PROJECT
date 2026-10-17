<?php

namespace App\Console\Commands;

use App\Services\KegiatanService;
use Illuminate\Console\Command;

/**
 * Menutup kegiatan yang sudah berlangsung lebih dari 24 jam sejak dibuat / dibuka kembali (revisi).
 * Penutupan juga diperiksa setiap halaman Kegiatan/Presensi dibuka; perintah ini menjadi cadangan.
 */
class TutupKegiatanOtomatis extends Command
{
    protected $signature = 'kegiatan:tutup-otomatis';

    protected $description = 'Menyelesaikan kegiatan yang melewati batas 24 jam';

    public function handle(KegiatanService $layanan): int
    {
        $jumlah = $layanan->tutupYangKedaluwarsa();
        $this->info("Kegiatan yang ditutup otomatis: {$jumlah}.");

        return self::SUCCESS;
    }
}
