<?php

namespace App\Services\Pemeriksaan;

use App\Enums\StatusAks;
use App\Enums\StatusImt;
use App\Enums\StatusPpok;
use App\Enums\StatusRisiko;
use App\Enums\StatusSensorik;
use App\Enums\StatusTekananDarah;
use App\Models\Warga;
use Carbon\CarbonInterface;

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

    /** GDS Berisiko bila ≥ 200 mg/dl (disetujui); dihitung dari angka, tanpa kolom status. */
    public static function statusGulaDarah(mixed $kadarMgDl): ?StatusRisiko
    {
        if (! is_numeric($kadarMgDl)) {
            return null;
        }

        return (float) $kadarMgDl >= (float) config('posyandu.ambang.gula_darah_berisiko') ? StatusRisiko::Berisiko : StatusRisiko::Normal;
    }

    /** Gangguan bila salah satu sisi (kanan/kiri) Gangguan; null bila keduanya belum diisi. */
    public static function statusSensorik(?string $kanan, ?string $kiri): ?StatusSensorik
    {
        if ($kanan === null && $kiri === null) {
            return null;
        }

        return in_array(StatusSensorik::Gangguan->value, [$kanan, $kiri], true) ? StatusSensorik::Gangguan : StatusSensorik::Normal;
    }

    /** Usia (tahun penuh) warga pada tanggal pemeriksaan. */
    public static function usia(Warga $warga, CarbonInterface $tanggal): int
    {
        return (int) $warga->tanggal_lahir->diffInYears($tanggal);
    }

    /** PUMA: laki-laki = 1, perempuan = 0. */
    public static function skorPumaJenisKelamin(bool $lakiLaki): int
    {
        return $lakiLaki ? 1 : 0;
    }

    /** PUMA: usia 40–49 = 0, 50–59 = 1, ≥ 60 = 2 (batas di config). */
    public static function skorPumaUsia(int $usia): int
    {
        $skor = 0;
        foreach (config('posyandu.ambang.puma.skor_usia') as $mulai => $nilai) {
            if ($usia >= $mulai) {
                $skor = $nilai;
            }
        }

        return $skor;
    }

    /**
     * Total skor PUMA = jenis kelamin + usia + merokok + jumlah jawaban "Ya" (napas pendek, dahak, batuk, spirometri).
     * Null bila status merokok belum diisi (skor belum lengkap).
     *
     * @param  array<int, ?string>  $gejala  nilai '1' / '0'
     */
    public static function skorPuma(int $skorJenisKelamin, int $skorUsia, ?string $skorMerokok, array $gejala): ?int
    {
        if (! is_numeric($skorMerokok)) {
            return null;
        }

        return $skorJenisKelamin + $skorUsia + (int) $skorMerokok + count(array_filter($gejala, fn ($v) => $v === '1'));
    }

    /** Berisiko PPOK bila skor PUMA ≥ 6 (disetujui, mengikuti PDPI). */
    public static function statusPpok(?int $skor): ?StatusPpok
    {
        if ($skor === null) {
            return null;
        }

        return $skor >= (int) config('posyandu.ambang.puma.berisiko_mulai') ? StatusPpok::Berisiko : StatusPpok::TidakBerisiko;
    }

    /**
     * Total skor AKS; null bila masih ada item yang belum dipilih (total butuh ke-10 item).
     *
     * @param  array<int, ?string>  $skor
     */
    public static function skorAks(array $skor): ?int
    {
        foreach ($skor as $nilai) {
            if (! is_numeric($nilai)) {
                return null;
            }
        }

        return array_sum(array_map('intval', $skor));
    }

    /** Mandiri 20 · Ringan 12–19 · Sedang 9–11 · Berat 5–8 · Total 0–4 (Kartu Bantu Pemeriksaan Lansia). */
    public static function statusAks(?int $total): ?StatusAks
    {
        if ($total === null) {
            return null;
        }
        $batas = config('posyandu.ambang.aks');

        return match (true) {
            $total >= $batas['mandiri'] => StatusAks::Mandiri,
            $total >= $batas['ringan_mulai'] => StatusAks::Ringan,
            $total >= $batas['sedang_mulai'] => StatusAks::Sedang,
            $total >= $batas['berat_mulai'] => StatusAks::Berat,
            default => StatusAks::Total,
        };
    }

    /**
     * Kelompok SKILAS yang ditemukan masalah (centang = "Ya"/perlu perhatian). Tidak disimpan di database.
     *
     * @param  array<string, mixed>  $nilai  kolom tabel skrining_skilas (imunisasi_covid: 1 = sudah)
     * @return array<int, string> nama kelompok
     */
    public static function temuanSkilas(array $nilai): array
    {
        $ya = fn (string $k) => filter_var($nilai[$k] ?? false, FILTER_VALIDATE_BOOLEAN);
        $temuan = [];
        foreach (self::KELOMPOK_SKILAS as $kelompok => $kolom) {
            if (array_filter($kolom, $ya)) {
                $temuan[] = $kelompok;
            }
        }
        if (array_key_exists('imunisasi_covid', $nilai) && $nilai['imunisasi_covid'] !== null && ! $ya('imunisasi_covid')) {
            $temuan[] = 'Belum imunisasi COVID-19';
        }

        return $temuan;
    }

    /** Kelompok pertanyaan SKILAS (Kartu Bantu Pemeriksaan Lansia, C3). */
    public const KELOMPOK_SKILAS = [
        'Penurunan Kognitif' => ['orientasi_waktu_tempat', 'mengulang_tiga_kata'],
        'Keterbatasan Mobilitas' => ['tes_berdiri_kursi'],
        'Malnutrisi' => ['bb_berkurang', 'hilang_nafsu_makan', 'lila_kurang'],
        'Gangguan Penglihatan' => ['masalah_penglihatan', 'tes_melihat'],
        'Gangguan Pendengaran' => ['tes_berbisik'],
        'Gejala Depresi' => ['perasaan_sedih', 'kurang_minat'],
    ];

    /** Konfigurasi batas untuk kalkulator di layar (JavaScript). */
    public static function untukLayar(): array
    {
        return config('posyandu.ambang');
    }
}
