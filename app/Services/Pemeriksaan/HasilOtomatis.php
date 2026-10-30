<?php

namespace App\Services\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusAks;
use App\Enums\StatusImt;
use App\Enums\StatusLila;
use App\Enums\StatusPpok;
use App\Enums\StatusRisiko;
use App\Enums\StatusRujukan;
use App\Enums\StatusSensorik;
use App\Enums\StatusTekananDarah;
use App\Models\Pemeriksaan;

/**
 * Kartu "Hasil Otomatis" di Detail Riwayat Pemeriksaan: status yang dihitung sistem saat data disimpan
 * (IMT, tekanan darah, lingkar perut, gula darah, gejala TBC, PUMA, AKS, SKILAS, ploting ibu hamil, rujukan).
 * Hanya membaca kolom yang sudah tersimpan; tidak menghitung ulang aturan selain yang memang tidak disimpan
 * (nilai IMT, status gula darah, dan temuan SKILAS dihitung dengan Kalkulator yang sama seperti saat pemeriksaan).
 */
final class HasilOtomatis
{
    /**
     * @return array<int, array{label: string, teks: string, risiko: bool}>
     */
    public static function dari(Pemeriksaan $pemeriksaan): array
    {
        $pemeriksaan->loadMissing([
            'pengukuranDasar', 'skriningTbc', 'skriningSensorik', 'skriningPuma',
            'skriningAks', 'skriningSkilas', 'pemeriksaanBumil', 'penyuluhanRujukan',
        ]);
        $hasil = [];
        $tambah = function (string $label, string $teks, bool $risiko) use (&$hasil) {
            $hasil[] = ['label' => $label, 'teks' => $teks, 'risiko' => $risiko];
        };

        $ukur = $pemeriksaan->pengukuranDasar;
        if ($ukur) {
            if ($ukur->status_imt) {
                $imt = Kalkulator::imt($ukur->berat_badan, $ukur->tinggi_badan);
                $tambah('IMT', ($imt !== null ? self::angka($imt).' kg/m² — ' : '').$ukur->status_imt->label(),
                    $ukur->status_imt !== StatusImt::Normal);
            }
            if ($ukur->status_tekanan_darah) {
                $tambah('Tekanan darah', ($ukur->tekanan_darah ? $ukur->tekanan_darah.' mmHg — ' : '').$ukur->status_tekanan_darah->label(),
                    $ukur->status_tekanan_darah !== StatusTekananDarah::Normal);
            }
            if ($ukur->status_lingkar_perut) {
                $tambah('Lingkar perut', ($ukur->lingkar_perut !== null ? self::angka($ukur->lingkar_perut).' cm — ' : '').$ukur->status_lingkar_perut->label(),
                    $ukur->status_lingkar_perut === StatusRisiko::Berisiko);
            }
            if (($gds = Kalkulator::statusGulaDarah($ukur->kadar_gula_darah)) !== null) {
                $tambah('Gula darah (GDS)', self::angka($ukur->kadar_gula_darah).' mg/dl — '.$gds->label(), $gds === StatusRisiko::Berisiko);
            }
        }

        if ($tbc = $pemeriksaan->skriningTbc) {
            $tambah('Gejala TBC', $tbc->status_gejala_tbc->label(), $tbc->status_gejala_tbc === StatusRisiko::Berisiko);
        } elseif (in_array(JenisPemeriksaan::Rutin, $pemeriksaan->jenis_pemeriksaan, true)) {
            // Semua gejala "Tidak" tidak membuat baris skrining_tbc (bagian kosong tidak disimpan) → 0 gejala = Normal.
            $tambah('Gejala TBC', StatusRisiko::Normal->label(), false);
        }

        if ($sensorik = $pemeriksaan->skriningSensorik) {
            if ($sensorik->status_penglihatan) {
                $tambah('Penglihatan', $sensorik->status_penglihatan->label(), $sensorik->status_penglihatan === StatusSensorik::Gangguan);
            }
            if ($sensorik->status_pendengaran) {
                $tambah('Pendengaran', $sensorik->status_pendengaran->label(), $sensorik->status_pendengaran === StatusSensorik::Gangguan);
            }
        }

        if (($puma = $pemeriksaan->skriningPuma) && $puma->status_ppok) {
            $tambah('Skor PUMA', 'Skor '.$puma->skor_puma_total.' — '.$puma->status_ppok->label(), $puma->status_ppok === StatusPpok::Berisiko);
        }

        if (($aks = $pemeriksaan->skriningAks) && $aks->status_aks) {
            $tambah('Total AKS', 'Skor '.$aks->skor_total.' — '.$aks->status_aks->label(), $aks->status_aks !== StatusAks::Mandiri);
        }

        if ($skilas = $pemeriksaan->skriningSkilas) {
            $temuan = Kalkulator::temuanSkilas($skilas->getAttributes());
            $tambah('Hasil SKILAS', $temuan === [] ? 'Tidak ada temuan' : count($temuan).' temuan: '.implode(', ', $temuan), $temuan !== []);
        }

        if ($bumil = $pemeriksaan->pemeriksaanBumil) {
            if ($bumil->status_ploting_lila) {
                $tambah('Ploting LILA', $bumil->status_ploting_lila->label(), $bumil->status_ploting_lila === StatusLila::Kek);
            }
            if ($bumil->status_ploting_tekanan_darah) {
                $tambah('Ploting tekanan darah', $bumil->status_ploting_tekanan_darah->label(),
                    $bumil->status_ploting_tekanan_darah !== StatusTekananDarah::Normal);
            }
        }

        if ($rujukan = $pemeriksaan->penyuluhanRujukan) {
            $tambah('Status rujukan', $rujukan->status_rujukan->label(), $rujukan->status_rujukan === StatusRujukan::RujukPuskesmas);
        }

        return $hasil;
    }

    /** 25.80 → "25,8" (format angka Indonesia, tanpa nol di belakang). */
    public static function angka(mixed $nilai): string
    {
        $teks = rtrim(rtrim(number_format((float) $nilai, 2, '.', ''), '0'), '.');

        return str_replace('.', ',', $teks);
    }
}
