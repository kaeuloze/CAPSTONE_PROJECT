<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusRujukan;
use App\Models\Kegiatan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\Kalkulator;

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
     * "Skrining Penimbangan dan Pengukuran": BB, TB, Lingkar perut, Tekanan darah (2 kotak → "120/80").
     *
     * @param  array<int, string>  $wajib  kolom bertanda * (berbeda per kategori)
     */
    public static function penimbangan(array $wajib = ['berat_badan', 'tinggi_badan', 'lingkar_perut', 'td_sistolik', 'td_diastolik']): Bagian
    {
        return new Bagian(
            kunci: 'pengukuran',
            judul: 'Skrining Penimbangan dan Pengukuran',
            relasi: 'pengukuranDasar',
            aturan: [
                'berat_badan' => self::angka(1, 300),
                'tinggi_badan' => self::angka(40, 250),
                'lingkar_perut' => self::angka(20, 200),
                'td_sistolik' => ['nullable', 'integer', 'between:50,300', 'required_with:bagian.pengukuran.td_diastolik'],
                'td_diastolik' => ['nullable', 'integer', 'between:30,200', 'required_with:bagian.pengukuran.td_sistolik'],
            ],
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
            uraikan: function (array $db) {
                [$sistolik, $diastolik] = array_pad(explode('/', (string) ($db['tekanan_darah'] ?? '')), 2, null);

                return ['td_sistolik' => $sistolik ?: null, 'td_diastolik' => $diastolik ?: null];
            },
            susun: function (array $n) {
                $td = $n['td_sistolik'] !== null && $n['td_diastolik'] !== null ? "{$n['td_sistolik']}/{$n['td_diastolik']}" : null;
                unset($n['td_sistolik'], $n['td_diastolik']);

                return $n + ['tekanan_darah' => $td];
            },
        );
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
