<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusSensorik;
use App\Enums\StatusRujukan;
use App\Models\Kegiatan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;

/**
 * Bagian formulir yang dipakai lebih dari satu kategori (Remaja, Dewasa, Lansia, ...),
 * agar aturan, batas, dan cara simpannya seragam.
 */
class BagianUmum
{
    /** Aturan angka desimal tidak wajib dengan rentang wajar. */
    public static function angka(float $min, float $max): array
    {
        return ['nullable', 'numeric', "between:{$min},{$max}"];
    }

    /**
     * "Skrining Penimbangan dan Pengukuran": BB, TB, Lingkar perut, Tekanan darah (2 kotak → "120/80"),
     * ditambah isian khusus kategori tertentu (mis. LILA & kadar gula darah untuk Dewasa).
     *
     * @param  array<int, string>  $wajib  kolom bertanda * (berbeda per kategori)
     * @param  array<int, string>  $tambahan  kolom tambahan: 'lila', 'kadar_gula_darah'
     */
    public static function penimbangan(array $wajib = ['berat_badan', 'tinggi_badan', 'lingkar_perut', 'td_sistolik', 'td_diastolik'], array $tambahan = []): Bagian
    {
        $aturanTambahan = array_intersect_key([
            'lila' => self::angka(5, 60),               // cm
            'kadar_gula_darah' => self::angka(20, 600), // mg/dl, diisi Nakes
        ], array_flip($tambahan));

        return new Bagian(
            kunci: 'pengukuran',
            judul: 'Skrining Penimbangan dan Pengukuran',
            relasi: 'pengukuranDasar',
            aturan: [
                'berat_badan' => self::angka(1, 300),
                'tinggi_badan' => self::angka(40, 250),
                'lingkar_perut' => self::angka(20, 200),
            ] + self::aturanTekananDarah() + $aturanTambahan,
            wajib: $wajib,
            jenis: JenisPemeriksaan::Rutin,
            hitung: function (array $n, Warga $warga) {
                $imt = Kalkulator::imt($n['berat_badan'], $n['tinggi_badan']);

                return [
                    'status_imt' => Kalkulator::statusImt($imt),
                    'status_tekanan_darah' => Kalkulator::statusTekananDarah($n['td_sistolik'], $n['td_diastolik']),
                    'status_lingkar_perut' => Kalkulator::statusLingkarPerut($n['lingkar_perut'], (bool) $warga->jenis_kelamin),
                ];
            },
            uraikan: self::uraikanTekananDarah(...),
            susun: self::susunTekananDarah(...),
        );
    }

    /** Aturan 2 kotak tekanan darah (harus berpasangan). */
    public static function aturanTekananDarah(string $kunci = 'pengukuran'): array
    {
        return [
            'td_sistolik' => ['nullable', 'integer', 'between:50,300', "required_with:bagian.{$kunci}.td_diastolik"],
            'td_diastolik' => ['nullable', 'integer', 'between:30,200', "required_with:bagian.{$kunci}.td_sistolik"],
        ];
    }

    /** Kolom "120/80" → 2 kotak sistolik & diastolik. */
    public static function uraikanTekananDarah(array $db): array
    {
        [$sistolik, $diastolik] = array_pad(explode('/', (string) ($db['tekanan_darah'] ?? '')), 2, null);

        return ['td_sistolik' => $sistolik ?: null, 'td_diastolik' => $diastolik ?: null];
    }

    /** 2 kotak sistolik & diastolik → kolom "120/80". */
    public static function susunTekananDarah(array $n): array
    {
        $td = $n['td_sistolik'] !== null && $n['td_diastolik'] !== null ? "{$n['td_sistolik']}/{$n['td_diastolik']}" : null;
        unset($n['td_sistolik'], $n['td_diastolik']);

        return $n + ['tekanan_darah' => $td];
    }

    /** "Skrining Gejala TBC": 4 centang; Berisiko bila ≥ 2 gejala "Ya". */
    public static function tbc(): Bagian
    {
        $kolom = ['batuk_terus_menerus', 'demam_2_minggu', 'bb_tidak_stabil', 'kontak_pasien_tbc'];

        return new Bagian(
            kunci: 'tbc',
            judul: 'Skrining Gejala TBC',
            relasi: 'skriningTbc',
            aturan: array_fill_keys($kolom, ['nullable', 'boolean']),
            boolean: $kolom,
            jenis: JenisPemeriksaan::Rutin,
            hitung: fn (array $n) => ['status_gejala_tbc' => Kalkulator::statusTbc($n)],
            catatBilaKosong: true, // semua gejala "Tidak" tetap tercatat Normal (Dashboard "Tidak Bergejala")
        );
    }

    /** Tab 6 bulan: Tes Penglihatan (Hitung Jari) & Tes Pendengaran (Berbisik), pilihan Normal / Gangguan. */
    public static function sensorik(): Bagian
    {
        $pilihan = ['nullable', Rule::enum(StatusSensorik::class)];

        return new Bagian(
            kunci: 'sensorik',
            judul: 'Tes Penglihatan & Pendengaran',
            relasi: 'skriningSensorik',
            aturan: ['mata_kanan' => $pilihan, 'mata_kiri' => $pilihan, 'telinga_kanan' => $pilihan, 'telinga_kiri' => $pilihan],
            jenis: JenisPemeriksaan::EnamBulanan,
            hitung: fn (array $n) => [
                'status_penglihatan' => Kalkulator::statusSensorik($n['mata_kanan'], $n['mata_kiri']),
                'status_pendengaran' => Kalkulator::statusSensorik($n['telinga_kanan'], $n['telinga_kiri']),
            ],
        );
    }

    /**
     * Tab Tahunan: "Skrining PPOK PUMA" (khusus usia ≥ 40 tahun). Skor jenis kelamin & usia dihitung otomatis
     * dari data warga (disetujui); kader mengisi status merokok + 4 pertanyaan Ya/Tidak. Berisiko bila skor ≥ 6.
     */
    public static function puma(): Bagian
    {
        $gejala = ['gejala_napas_pendek', 'gejala_dahak', 'gejala_batuk_tanpa_flu', 'pernah_spirometri'];

        return new Bagian(
            kunci: 'puma',
            judul: 'Skrining PPOK PUMA',
            relasi: 'skriningPuma',
            aturan: ['skor_merokok' => ['nullable', 'integer', 'in:0,1,2']] + array_fill_keys($gejala, ['nullable', 'boolean']),
            boolean: $gejala,
            jenis: JenisPemeriksaan::Tahunan,
            hitung: function (array $n, Warga $warga, Kegiatan $kegiatan) use ($gejala) {
                $skorJk = Kalkulator::skorPumaJenisKelamin((bool) $warga->jenis_kelamin);
                $skorUsia = Kalkulator::skorPumaUsia(Kalkulator::usia($warga, $kegiatan->tanggal_kegiatan));
                $total = Kalkulator::skorPuma($skorJk, $skorUsia, $n['skor_merokok'], array_map(fn ($k) => $n[$k], $gejala));

                return [
                    'skor_jenis_kelamin' => $skorJk,
                    'skor_usia' => $skorUsia,
                    'skor_puma_total' => $total,
                    'status_ppok' => Kalkulator::statusPpok($total),
                ];
            },
            berlaku: fn (Warga $warga, CarbonInterface $tanggal) => Kalkulator::usia($warga, $tanggal) >= (int) config('posyandu.ambang.puma.usia_minimal'),
            wajibBilaTerisi: ['skor_merokok'],
        );
    }

    /** "Penyuluhan" + centang Rujukan ke Puskesmas (keputusan 15: tanpa pustu). */
    public static function penyuluhan(): Bagian
    {
        return new Bagian(
            kunci: 'penyuluhan',
            judul: 'Penyuluhan',
            relasi: 'penyuluhanRujukan',
            aturan: [
                'topik_penyuluhan' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
                'dirujuk' => ['nullable', 'boolean'],
            ],
            boolean: ['dirujuk'],
            jenis: JenisPemeriksaan::Rutin,
            hitung: fn (array $n, Warga $warga, Kegiatan $kegiatan) => [
                'tanggal_rujukan' => $n['dirujuk'] === '1' ? $kegiatan->tanggal_kegiatan->toDateString() : null,
            ],
            uraikan: fn (array $db) => [
                'dirujuk' => ($db['status_rujukan'] ?? null) === StatusRujukan::RujukPuskesmas->value ? '1' : '0',
            ],
            susun: function (array $n) {
                $status = $n['dirujuk'] === '1' ? StatusRujukan::RujukPuskesmas : StatusRujukan::TidakDirujuk;
                unset($n['dirujuk']);

                return $n + ['status_rujukan' => $status];
            },
        );
    }
}
