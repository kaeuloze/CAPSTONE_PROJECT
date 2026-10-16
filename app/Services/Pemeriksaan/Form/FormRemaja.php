<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisPemeriksaan;
use App\Enums\KategoriPemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\DefinisiForm;
use Carbon\CarbonInterface;

/**
 * Form Pemeriksaan Remaja (Figma 418:65, 424:65, 424:168 "hasil rev"; proposal Gambar 2.48–2.50).
 * Tab: Rutin Bulanan | Screening 6-Bulan | Tahunan Remaja Putri (dinonaktifkan untuk laki-laki).
 */
class FormRemaja extends DefinisiForm
{
    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::Remaja;
    }

    public function judul(): string
    {
        $mulai = (int) config('posyandu.kategori_usia.remaja_mulai');
        $sampai = (int) config('posyandu.kategori_usia.dewasa_mulai') - 1;

        return "Pemeriksaan — Remaja ({$mulai} - {$sampai} Tahun)";
    }

    public function tab(): array
    {
        $tidakWajib = 'Isian di tab ini tidak wajib (diisi bila pemeriksaan dilakukan). Tombol Submit aktif setelah semua isian bertanda * di tab Rutin Bulanan terisi.';

        return [
            'rutin' => ['label' => 'Rutin Bulanan', 'bagian' => ['pengukuran', 'tbc']],
            'enam_bulan' => ['label' => 'Screening 6-Bulan', 'bagian' => ['sensorik'], 'jenis' => JenisPemeriksaan::EnamBulanan, 'catatan' => $tidakWajib],
            'tahunan' => ['label' => 'Tahunan Remaja Putri', 'bagian' => ['hb'], 'jenis' => JenisPemeriksaan::Tahunan, 'catatan' => $tidakWajib],
        ];
    }

    public function bagianUmum(): array
    {
        return ['penyuluhan'];
    }

    public function tabNonaktif(Warga $warga, CarbonInterface $tanggalPeriksa): array
    {
        return $warga->jenis_kelamin ? ['tahunan' => 'Khusus remaja putri'] : [];
    }

    public function viewTambahan(): ?string
    {
        return 'pemeriksaan.form.remaja._hasil';
    }

    public function view(): string
    {
        return 'pemeriksaan.form.remaja';
    }

    protected function daftarBagian(): array
    {
        return [
            BagianUmum::penimbangan(),
            BagianUmum::tbc(),
            BagianUmum::sensorik(),
            new Bagian(
                kunci: 'hb',
                judul: 'Pemeriksaan Kadar HB',
                relasi: 'pengukuranDasar',
                aturan: ['kadar_hb' => BagianUmum::angka(1, 25)],
                jenis: JenisPemeriksaan::Tahunan,
                berlaku: fn (Warga $w) => ! $w->jenis_kelamin, // hanya remaja putri
            ),
            BagianUmum::penyuluhan(),
        ];
    }
}
