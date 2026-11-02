<?php

use App\Enums\KategoriPemeriksaan;
use App\Services\Pemeriksaan\Kalkulator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Batas tekanan darah diubah (pengecekan bersama kader, 31 Okt 2026): Remaja Tinggi > 130 / ≥ 85,
 * Dewasa/Lansia/Ibu Tinggi ≥ 140/90, semua kategori Rendah < 90/70. Status yang sudah tersimpan
 * dihitung ulang dari angka tekanan_darah ("120/80") agar Rekapitulasi & Dashboard memakai batas baru:
 * pengukuran_dasar.status_tekanan_darah dan pemeriksaan_bumil.status_ploting_tekanan_darah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pengukuran_dasar as u')
            ->join('pemeriksaan as p', 'p.id_pemeriksaan', '=', 'u.id_pemeriksaan')
            ->whereNotNull('u.tekanan_darah')
            ->select(['u.id_pengukuran', 'u.id_pemeriksaan', 'u.tekanan_darah', 'p.kategori_usia_saat_periksa'])
            ->orderBy('u.id_pengukuran')
            ->chunk(500, function ($baris) {
                foreach ($baris as $b) {
                    [$sistolik, $diastolik] = array_pad(explode('/', $b->tekanan_darah), 2, null);
                    $kelompok = Kalkulator::kelompokTekananDarah(KategoriPemeriksaan::tryFrom($b->kategori_usia_saat_periksa));
                    $status = Kalkulator::statusTekananDarah($sistolik, $diastolik, $kelompok)?->value;

                    DB::table('pengukuran_dasar')->where('id_pengukuran', $b->id_pengukuran)
                        ->update(['status_tekanan_darah' => $status]);
                    // Ploting TD ibu hamil/nifas memakai batas dewasa (kelompok di atas sudah 'dewasa' untuk ibu).
                    DB::table('pemeriksaan_bumil')->where('id_pemeriksaan', $b->id_pemeriksaan)
                        ->whereNotNull('status_ploting_tekanan_darah')
                        ->update(['status_ploting_tekanan_darah' => $status]);
                }
            });
    }

    /** Tidak dibatalkan: status selalu bisa dihitung ulang dari angka tekanan darah. */
    public function down(): void
    {
    }
};
