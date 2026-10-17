<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisPemeriksaan;
use App\Enums\KategoriPemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\DefinisiForm;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonInterface;

/**
 * Form Pemeriksaan Dewasa (Figma 430:65, 430:201, 430:304 "hasil rev").
 * Tab: Pemeriksaan Rutin Bulanan | Pemeriksaan 6 Bulan Sekali | Pemeriksaan Tahunan (PUMA, khusus usia ≥ 40).
 */
class FormDewasa extends DefinisiForm
{
    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::Dewasa;
    }

    public function judul(): string
    {
        $mulai = (int) config('posyandu.kategori_usia.dewasa_mulai');
        $sampai = (int) config('posyandu.kategori_usia.lansia_mulai') - 1;

        return "Pemeriksaan — Dewasa ({$mulai} - {$sampai} Tahun)";
    }

    public function tab(): array
    {
        $tidakWajib = 'Isian di tab ini tidak wajib (diisi bila pemeriksaan dilakukan). Tombol Submit aktif setelah semua isian bertanda * di tab Rutin Bulanan terisi.';

        return [
            'rutin' => ['label' => 'Pemeriksaan Rutin Bulanan', 'bagian' => ['pengukuran', 'tbc']],
            'enam_bulan' => ['label' => 'Pemeriksaan 6 Bulan Sekali', 'bagian' => ['sensorik'], 'jenis' => JenisPemeriksaan::EnamBulanan, 'catatan' => $tidakWajib],
            'tahunan' => ['label' => 'Pemeriksaan Tahunan', 'bagian' => ['puma'], 'jenis' => JenisPemeriksaan::Tahunan, 'catatan' => $tidakWajib],
        ];
    }

    public function bagianUmum(): array
    {
        return ['penyuluhan'];
    }

    /** PUMA khusus usia ≥ 40 tahun (disetujui; formulir kertas "≥ 40"). */
    public function tabNonaktif(Warga $warga, CarbonInterface $tanggalPeriksa): array
    {
        $minimal = (int) config('posyandu.ambang.puma.usia_minimal');

        return Kalkulator::usia($warga, $tanggalPeriksa) < $minimal ? ['tahunan' => "Khusus usia {$minimal} tahun ke atas"] : [];
    }

    public function viewTambahan(): ?string
    {
        return 'pemeriksaan.form.dewasa._hasil';
    }

    public function view(): string
    {
        return 'pemeriksaan.form.dewasa';
    }

    protected function daftarBagian(): array
    {
        return [
            BagianUmum::penimbangan(
                wajib: ['berat_badan', 'tinggi_badan', 'lingkar_perut', 'lila', 'td_sistolik', 'td_diastolik'],
                tambahan: ['lila', 'kadar_gula_darah'],
            ),
            BagianUmum::tbc(),
            BagianUmum::sensorik(),
            BagianUmum::puma(),
            BagianUmum::penyuluhan(),
        ];
    }
}
