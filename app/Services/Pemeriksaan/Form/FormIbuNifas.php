<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisIbu;
use App\Enums\KategoriPemeriksaan;
use App\Models\Warga;
use Carbon\CarbonInterface;

/**
 * Form Pemeriksaan Ibu Nifas / Menyusui (Figma 430:1099 "hasil rev" + foto Kartu Bantu Ibu Nifas/Menyusui).
 * Satu form dengan Ibu Hamil: tab "Ibu Hamil" dikunci untuk ibu nifas/menyusui (disetujui). Ibu menyusui memakai form ini
 * (keputusan 39).
 */
class FormIbuNifas extends FormIbuHamil
{
    /** "Waktu ke Posyandu" setelah bersalin sesuai kolom kartu kertas (disetujui ditambahkan; Figma belum memuat). */
    public const WAKTU_NIFAS = [
        '<7 hari' => '< 7 hari', '7-28 hari' => '7 - 28 hari', '28-42 hari' => '28 - 42 hari',
        'Bulan 2' => 'Bulan 2', 'Bulan 3' => 'Bulan 3', 'Bulan 4' => 'Bulan 4', 'Bulan 5' => 'Bulan 5', 'Bulan 6' => 'Bulan 6',
        'Bulan 7' => 'Bulan 7', 'Bulan 8' => 'Bulan 8', 'Bulan 9' => 'Bulan 9', 'Bulan 10' => 'Bulan 10', 'Bulan 11' => 'Bulan 11',
        'Bulan 12' => 'Bulan 12', 'Bulan 13' => 'Bulan 13', 'Bulan 14' => 'Bulan 14', 'Bulan 15' => 'Bulan 15', 'Bulan 16' => 'Bulan 16',
        'Bulan 17' => 'Bulan 17', 'Bulan 18' => 'Bulan 18', 'Bulan 19' => 'Bulan 19', 'Bulan 20' => 'Bulan 20', 'Bulan 21' => 'Bulan 21',
        'Bulan 22' => 'Bulan 22', 'Bulan 23' => 'Bulan 23', 'Bulan 24' => 'Bulan 24',
    ];

    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::IbuNifas;
    }

    public function tab(): array
    {
        return [
            'hamil' => ['label' => 'Ibu Hamil', 'bagian' => []],
            'nifas' => ['label' => 'Ibu Nifas', 'bagian' => ['waktu', 'pengukuran', 'ploting_imt', 'tbc', 'pelayanan']],
        ];
    }

    public function tabNonaktif(Warga $warga, CarbonInterface $tanggalPeriksa): array
    {
        return ['hamil' => 'Status ibu saat ini: '.($warga->status_kondisi?->label() ?? 'Ibu Nifas')];
    }

    public function viewTambahan(): ?string
    {
        return 'pemeriksaan.form.ibu-nifas._hasil';
    }

    public function view(): string
    {
        return 'pemeriksaan.form.ibu-nifas';
    }

    protected function jenisIbu(): JenisIbu
    {
        return JenisIbu::Nifas;
    }

    protected function daftarBagian(): array
    {
        return [
            $this->bagianRentang('waktu', 'Waktu ke Posyandu Setelah Bersalin', self::WAKTU_NIFAS),
            $this->bagianPengukuran(['berat_badan' => BagianUmum::angka(1, 300)]),
            $this->bagianPlotingImt(),
            BagianUmum::tbc(),
            $this->bagianBumil('pelayanan', 'Pelayanan Kesehatan', [
                'jumlah_kapsul_vit_a' => ['nullable', 'integer', 'between:0,10'],
                'status_rutin_vit_a' => ['nullable', 'boolean'],
                'status_menyusui' => ['nullable', 'boolean'],
                'status_kb_pascasalin' => ['nullable', 'boolean'],
            ], ['status_rutin_vit_a', 'status_menyusui', 'status_kb_pascasalin']),
            BagianUmum::penyuluhan(),
        ];
    }
}
