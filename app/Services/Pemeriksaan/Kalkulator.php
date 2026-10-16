<?php

namespace App\Services\Pemeriksaan;

use App\Enums\StatusImt;
use App\Enums\StatusRisiko;
use App\Enums\StatusSensorik;
use App\Enums\StatusTekananDarah;

/**
 * Perhitungan status otomatis hasil pemeriksaan (keputusan 5). Batas diambil dari
 * config/posyandu.php ('ambang'), mengikuti format resmi Rekapitulasi (Figma 306:103).
 */
class Kalkulator
{
    /** IMT = BB (kg) / (TB (m))², dibulatkan 1 desimal. */
    public static function imt(?string $beratKg, ?string $tinggiCm): ?float
    {
        if (! is_numeric($beratKg) || ! is_numeric($tinggiCm) || (float) $tinggiCm <= 0) {
            return null;
        }

        return round((float) $beratKg / (((float) $tinggiCm / 100) ** 2), 1);
    }

    /** Kurus < 18,5 ≤ Normal < 25,0 ≤ Gemuk ≤ 27,0 < Obesitas. Rekap: Normal vs Risiko (kurus/gemuk/obesitas). */
    public static function statusImt(?float $imt): ?StatusImt
    {
        if ($imt === null) {
            return null;
        }
        $batas = config('posyandu.ambang.imt');

        return match (true) {
            $imt < $batas['normal_mulai'] => StatusImt::Kurus,
            $imt < $batas['gemuk_mulai'] => StatusImt::Normal,
            $imt <= $batas['gemuk_sampai'] => StatusImt::Gemuk,
            default => StatusImt::Obesitas,
        };
    }

    /** Tinggi bila sistolik ≥ 130 atau diastolik ≥ 85; selain itu Normal (keputusan: tanpa status "rendah"). */
    public static function statusTekananDarah(?string $sistolik, ?string $diastolik): ?StatusTekananDarah
    {
        if (! is_numeric($sistolik) || ! is_numeric($diastolik)) {
            return null;
        }
        $batas = config('posyandu.ambang.tekanan_darah');

        return ((int) $sistolik >= $batas['sistolik_tinggi'] || (int) $diastolik >= $batas['diastolik_tinggi'])
            ? StatusTekananDarah::Tinggi
            : StatusTekananDarah::Normal;
    }

    /** Berisiko bila lingkar perut > 80 cm (perempuan) / > 90 cm (laki-laki). */
    public static function statusLingkarPerut(?string $lingkarCm, bool $lakiLaki): ?StatusRisiko
    {
        if (! is_numeric($lingkarCm)) {
            return null;
        }
        $batas = config('posyandu.ambang.lingkar_perut')[$lakiLaki ? 'laki_laki' : 'perempuan'];

        return (float) $lingkarCm > $batas ? StatusRisiko::Berisiko : StatusRisiko::Normal;
    }

    /** Berisiko bila jumlah gejala "Ya" ≥ 2 (formulir asli & rekap "memenuhi 2 gejala"). */
    public static function statusTbc(array $centang): StatusRisiko
    {
        $jumlahYa = count(array_filter($centang, fn ($v) => $v === '1'));

        return $jumlahYa >= (int) config('posyandu.ambang.tbc_minimal_gejala') ? StatusRisiko::Berisiko : StatusRisiko::Normal;
    }

    /** Gangguan bila salah satu sisi (kanan/kiri) Gangguan; null bila keduanya belum diisi. */
    public static function statusSensorik(?string $kanan, ?string $kiri): ?StatusSensorik
    {
        if ($kanan === null && $kiri === null) {
            return null;
        }

        return in_array(StatusSensorik::Gangguan->value, [$kanan, $kiri], true) ? StatusSensorik::Gangguan : StatusSensorik::Normal;
    }

    /** Konfigurasi batas untuk kalkulator di layar (JavaScript). */
    public static function untukLayar(): array
    {
        return config('posyandu.ambang');
    }
}
