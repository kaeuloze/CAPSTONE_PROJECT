<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisPemeriksaan;
use App\Enums\KategoriPemeriksaan;
use App\Models\SkriningAks;
use App\Models\SkriningSkilas;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\DefinisiForm;
use App\Services\Pemeriksaan\Kalkulator;

/**
 * Form Pemeriksaan Lansia (Figma 430:408, 430:580, 430:683 "hasil rev"; Kartu Bantu Pemeriksaan Lansia ≥ 60 tahun).
 * Tab: Pemeriksaan Rutin Bulanan | Pemeriksaan 6 Bulan Sekali | Pemeriksaan Tahunan (PUMA, AKS, SKILAS).
 */
class FormLansia extends DefinisiForm
{
    /**
     * Rentang skor tiap item AKS sesuai formulir kertas (disetujui; Figma "0/1/2" dikoreksi):
     * Membersihkan diri & Mandi 0–1, Bergerak dari kursi roda & Berjalan 0–3, lainnya 0–2.
     */
    public const SKOR_MAKS_AKS = [
        'skor_bab' => 2, 'skor_bak' => 2, 'skor_kebersihan_diri' => 1, 'skor_penggunaan_wc' => 2, 'skor_makan_minum' => 2,
        'skor_transfer_kursi' => 3, 'skor_berjalan' => 3, 'skor_berpakaian' => 2, 'skor_naik_tangga' => 2, 'skor_mandi' => 1,
    ];

    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::Lansia;
    }

    public function judul(): string
    {
        return 'Pemeriksaan — Lansia (>= '.(int) config('posyandu.kategori_usia.lansia_mulai').' Tahun)';
    }

    public function tab(): array
    {
        $tidakWajib = 'Isian di tab ini tidak wajib (diisi bila pemeriksaan dilakukan). Tombol Submit aktif setelah semua isian bertanda * di tab Rutin Bulanan terisi.';

        return [
            'rutin' => ['label' => 'Pemeriksaan Rutin Bulanan', 'bagian' => ['pengukuran', 'tbc']],
            'enam_bulan' => ['label' => 'Pemeriksaan 6 Bulan Sekali', 'bagian' => ['sensorik'], 'jenis' => JenisPemeriksaan::EnamBulanan, 'catatan' => $tidakWajib],
            'tahunan' => ['label' => 'Pemeriksaan Tahunan', 'bagian' => ['puma', 'aks', 'skilas'], 'jenis' => JenisPemeriksaan::Tahunan, 'catatan' => $tidakWajib],
        ];
    }

    public function bagianUmum(): array
    {
        return ['penyuluhan'];
    }

    public function viewTambahan(): ?string
    {
        return 'pemeriksaan.form.lansia._hasil';
    }

    public function view(): string
    {
        return 'pemeriksaan.form.lansia';
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
            $this->aks(),
            $this->skilas(),
            BagianUmum::penyuluhan(),
        ];
    }

    /** "Pemeriksaan tahunan Skrining Aktifitas Kehidupan Sehari-hari (AKS)": 10 item, wajib lengkap bila diisi (disetujui). */
    private function aks(): Bagian
    {
        $aturan = [];
        foreach (self::SKOR_MAKS_AKS as $kolom => $maks) {
            $aturan[$kolom] = ['nullable', 'integer', "between:0,{$maks}"];
        }

        return new Bagian(
            kunci: 'aks',
            judul: 'Skrining Aktivitas Kehidupan Sehari-hari (AKS)',
            relasi: 'skriningAks',
            aturan: $aturan,
            jenis: JenisPemeriksaan::Tahunan,
            hitung: function (array $n) {
                $total = Kalkulator::skorAks(array_values(array_intersect_key($n, self::SKOR_MAKS_AKS)));

                return ['skor_total' => $total, 'status_aks' => Kalkulator::statusAks($total)];
            },
            wajibBilaTerisi: SkriningAks::ITEM_SKOR,
        );
    }

    /**
     * "Pemeriksaan Tahunan SKILAS": 12 centang (centang = "Ya"/ditemukan masalah). Item COVID ditampilkan sebagai
     * "Belum mendapat imunisasi COVID-19" (disetujui) agar semua centang bermakna sama; di database kolom
     * imunisasi_covid tetap bermakna 1 = sudah imunisasi.
     */
    private function skilas(): Bagian
    {
        $centang = [...array_diff(SkriningSkilas::ITEM, ['imunisasi_covid']), 'belum_imunisasi_covid'];

        return new Bagian(
            kunci: 'skilas',
            judul: 'Pemeriksaan Tahunan SKILAS',
            relasi: 'skriningSkilas',
            aturan: array_fill_keys($centang, ['nullable', 'boolean']),
            boolean: $centang,
            jenis: JenisPemeriksaan::Tahunan,
            uraikan: fn (array $db) => [
                'belum_imunisasi_covid' => array_key_exists('imunisasi_covid', $db) && $db['imunisasi_covid'] !== null && ! (bool) $db['imunisasi_covid'] ? '1' : '0',
            ],
            susun: function (array $n) {
                $belum = $n['belum_imunisasi_covid'] === '1';
                unset($n['belum_imunisasi_covid']);

                return $n + ['imunisasi_covid' => ! $belum];
            },
        );
    }
}
