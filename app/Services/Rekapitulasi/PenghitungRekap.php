<?php

namespace App\Services\Rekapitulasi;

use App\Enums\JenisIbu;
use App\Enums\KategoriPemeriksaan;
use App\Enums\KategoriRekap;
use App\Enums\KategoriUsia;
use App\Enums\StatusImt;
use App\Enums\StatusLila;
use App\Enums\StatusPpok;
use App\Enums\StatusRisiko;
use App\Enums\StatusRujukan;
use App\Enums\StatusSensorik;
use App\Enums\StatusTekananDarah;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung baris rekapitulasi format Kemenkes (SKF-12; Figma 306:103, 316:55, 329:79; foto formulir kertas).
 *
 * Aturan hitung (disetujui 21 Okt 2026):
 * 1. Hanya pemeriksaan Submit; bila ada beberapa kegiatan dalam sebulan, tiap warga dihitung sekali
 *    (per indikator diambil dari pemeriksaan terakhirnya yang memuat indikator itu).
 * 2. "Datang" = warga Hadir di kegiatan bulan itu; "Jumlah sasaran" = warga terdaftar di kelompok itu
 *    pada tanggal kegiatan terakhir bulan itu (tanpa kegiatan: akhir bulan / hari ini).
 * 3. Ibu hamil/nifas/menyusui tidak masuk sasaran Remaja/Dewasa/Lansia (sama dengan kartu Kegiatan);
 *    status hamil/nifas memakai status warga saat ini (tidak punya riwayat).
 */
class PenghitungRekap
{
    /** @var Collection<int, Kegiatan>|null */
    private ?Collection $kegiatan = null;

    private ?Periode $periodeKegiatan = null;

    /** Kegiatan dalam periode, urut tanggal. */
    public function kegiatanDalam(Periode $periode): Collection
    {
        if ($this->periodeKegiatan != $periode) {
            $this->kegiatan = Kegiatan::query()
                ->select(['id_kegiatan', 'nama_kegiatan', 'tanggal_kegiatan'])
                ->whereBetween('tanggal_kegiatan', [$periode->awal()->toDateString(), $periode->akhir()->toDateString()])
                ->orderBy('tanggal_kegiatan')->orderBy('id_kegiatan')
                ->get();
            $this->periodeKegiatan = $periode;
        }

        return $this->kegiatan;
    }

    /** Tanggal acuan sasaran: tanggal kegiatan terakhir, atau akhir bulan (paling lambat hari ini). */
    public function tanggalAcuan(Periode $periode): CarbonImmutable
    {
        $terakhir = $this->kegiatanDalam($periode)->last();
        if ($terakhir) {
            return CarbonImmutable::parse($terakhir->tanggal_kegiatan)->startOfDay();
        }

        return $periode->akhir()->min(CarbonImmutable::today());
    }

    /** @return list<ItemRekap> */
    public function hitung(KategoriRekap $kategori, Periode $periode): array
    {
        return match ($kategori) {
            KategoriRekap::Remaja => $this->remaja($periode),
            KategoriRekap::IbuHamilNifas => $this->ibuHamilNifas($periode),
            KategoriRekap::DewasaLansia => $this->dewasaLansia($periode),
        };
    }

    // ───────────────────────── Per kategori ─────────────────────────

    /** @return list<ItemRekap> */
    private function remaja(Periode $periode): array
    {
        $sasaran = $this->sasaran($periode);
        $datang = $this->datang($periode);
        $data = $this->pemeriksaan($periode, [KategoriPemeriksaan::Remaja]);
        $rentang = $this->rentangUsia(KategoriUsia::Remaja);
        $hbBatas = $this->angka((float) config('posyandu.ambang.hb_remaja_putri'));
        $hb = fn ($p) => ! $p->warga->jenis_kelamin && $p->pengukuranDasar?->kadar_hb !== null ? (float) $p->pengukuranDasar->kadar_hb : null;
        $hbNormal = (float) config('posyandu.ambang.hb_remaja_putri');

        return [
            new ItemRekap('SASARAN_REMAJA', "Jumlah Remaja {$rentang} Tahun", $sasaran['remaja']),
            new ItemRekap('DATANG_REMAJA', "Jumlah Remaja {$rentang} Tahun Datang", $datang['remaja']),
            ...$this->barisImt($data),
            ...$this->barisLingkarPerut($data, 'LP Remaja'),
            ...$this->barisTekananDarah($data, 'TD Remaja'),
            $this->barisTbc($data),
            new ItemRekap('HB_NORMAL', "Kadar HB Normal Remaja Putri (≥ {$hbBatas} g/dL)", $this->hitungWarga($data, $hb, fn ($v) => $v >= $hbNormal)),
            new ItemRekap('HB_RISIKO', "Kadar HB Risiko Remaja Putri (< {$hbBatas} g/dL)", $this->hitungWarga($data, $hb, fn ($v) => $v < $hbNormal)),
            ...$this->barisSensorik($data),
            new ItemRekap('EDUKASI', 'Sasaran Mendapat Edukasi / Penyuluhan',
                $this->hitungWarga($data, fn ($p) => filled($p->penyuluhanRujukan?->topik_penyuluhan) ? true : null, fn ($v) => $v)),
            new ItemRekap('DIRUJUK_REMAJA', "Jumlah Remaja {$rentang} Tahun Dirujuk",
                $this->hitungWarga($data, fn ($p) => $p->penyuluhanRujukan?->status_rujukan === StatusRujukan::RujukPuskesmas ? true : null, fn ($v) => $v)),
        ];
    }

    /** @return list<ItemRekap> */
    private function dewasaLansia(Periode $periode): array
    {
        $sasaran = $this->sasaran($periode);
        $datang = $this->datang($periode);
        $data = $this->pemeriksaan($periode, [KategoriPemeriksaan::Dewasa, KategoriPemeriksaan::Lansia]);
        $dewasa = $this->rentangUsia(KategoriUsia::Dewasa);
        $lansia = KategoriUsia::Lansia->usiaMulai();
        $gdsBatas = (float) config('posyandu.ambang.gula_darah_berisiko');
        $gds = fn ($p) => Kalkulator::statusGulaDarah($p->pengukuranDasar?->kadar_gula_darah);
        $pumaBatas = (int) config('posyandu.ambang.puma.berisiko_mulai');
        $puma = fn ($p) => $p->skriningPuma?->status_ppok;

        return [
            new ItemRekap('SASARAN_DEWASA', "Jumlah Sasaran Dewasa ({$dewasa} Tahun)", $sasaran['dewasa']),
            new ItemRekap('SASARAN_LANSIA', "Jumlah Sasaran Lansia (≥ {$lansia} Tahun)", $sasaran['lansia']),
            new ItemRekap('DATANG_DEWASA', "Jumlah Dewasa ({$dewasa} Tahun) Datang", $datang['dewasa']),
            new ItemRekap('DATANG_LANSIA', "Jumlah Lansia (≥ {$lansia} Tahun) Datang", $datang['lansia']),
            ...$this->barisImt($data),
            ...$this->barisLingkarPerut($data, 'LP'),
            ...$this->barisTekananDarah($data, 'TD'),
            new ItemRekap('GDS_NORMAL', 'GDS Normal (< '.$this->angka($gdsBatas).' mg/dl)', $this->hitungWarga($data, $gds, fn ($s) => $s === StatusRisiko::Normal)),
            new ItemRekap('GDS_BERISIKO', 'GDS Berisiko (≥ '.$this->angka($gdsBatas).' mg/dl)', $this->hitungWarga($data, $gds, fn ($s) => $s === StatusRisiko::Berisiko)),
            $this->barisTbc($data),
            ...$this->barisSensorik($data),
            new ItemRekap('PUMA_NORMAL', "Skrining PPOK PUMA Normal (< {$pumaBatas})", $this->hitungWarga($data, $puma, fn ($s) => $s === StatusPpok::TidakBerisiko)),
            new ItemRekap('PUMA_BERISIKO', "Skrining PPOK PUMA Berisiko (≥ {$pumaBatas})", $this->hitungWarga($data, $puma, fn ($s) => $s === StatusPpok::Berisiko)),
        ];
    }

    /** @return list<ItemRekap> */
    private function ibuHamilNifas(Periode $periode): array
    {
        $sasaran = $this->sasaran($periode);
        $datang = $this->datang($periode);
        $data = $this->pemeriksaan($periode, [KategoriPemeriksaan::IbuHamil, KategoriPemeriksaan::IbuNifas]);
        $hamil = $data->map(fn (Collection $daftar) => $daftar->filter(fn ($p) => $p->pemeriksaanBumil?->jenis_ibu === JenisIbu::Hamil)->values())
            ->filter(fn (Collection $daftar) => $daftar->isNotEmpty());

        $td = fn ($p) => $p->pemeriksaanBumil?->status_ploting_tekanan_darah;
        $tdTinggi = $this->batasTd();
        $lilaBatas = $this->angka((float) config('posyandu.ambang.lila_kek_di_bawah'));
        $lila = fn ($p) => $p->pemeriksaanBumil?->status_ploting_lila;
        // Pelayanan bumil (kartu kertas): mendapat = ada isian jumlah/komposisi; rutin = centang rutin.
        $ttd = fn ($p) => ($p->pemeriksaanBumil?->jumlah_ttd_mms ?? 0) > 0 ? (bool) $p->pemeriksaanBumil->status_konsumsi_ttd_rutin : null;
        $pmt = fn ($p) => filled(trim((string) $p->pemeriksaanBumil?->komposisi_mt_bumil_kek)) ? (bool) $p->pemeriksaanBumil->status_rutin_mt_kek : null;
        $kelas = fn ($p) => $p->pemeriksaanBumil ? (bool) $p->pemeriksaanBumil->ikut_kelas_ibu_hamil : null;
        $ya = fn ($v) => true;

        return [
            new ItemRekap('SASARAN_HAMIL', 'Jumlah Ibu Hamil', $sasaran['hamil']),
            new ItemRekap('SASARAN_NIFAS', 'Jumlah Ibu Nifas / Menyusui', $sasaran['nifas']),
            new ItemRekap('DATANG_HAMIL', 'Jumlah Ibu Hamil Datang', $datang['hamil']),
            new ItemRekap('DATANG_NIFAS', 'Jumlah Ibu Nifas / Menyusui Datang', $datang['nifas']),
            new ItemRekap('IMT_KIA', 'Jumlah Bumil/Bufas IMT Sesuai KIA',
                $this->hitungWarga($data, fn ($p) => $p->pemeriksaanBumil?->status_ploting_imt, fn ($s) => $s === StatusImt::Normal)),
            new ItemRekap('TD_NORMAL', "TD Normal (< {$tdTinggi})", $this->hitungWarga($data, $td, fn ($s) => $s !== StatusTekananDarah::Tinggi)),
            new ItemRekap('TD_RISIKO', "TD Risiko (≥ {$tdTinggi})", $this->hitungWarga($data, $td, fn ($s) => $s === StatusTekananDarah::Tinggi)),
            new ItemRekap('LILA_NORMAL', "LILA Normal (≥ {$lilaBatas} cm)", $this->hitungWarga($hamil, $lila, fn ($s) => $s === StatusLila::Normal)),
            new ItemRekap('LILA_KEK', "LILA KEK (< {$lilaBatas} cm)", $this->hitungWarga($hamil, $lila, fn ($s) => $s === StatusLila::Kek)),
            $this->barisTbc($data),
            new ItemRekap('TTD_MENDAPAT', 'Bumil Mendapat TTD/MMS', $this->hitungWarga($hamil, $ttd, $ya)),
            new ItemRekap('TTD_RUTIN', 'Bumil Konsumsi TTD/MMS Setiap Hari', $this->hitungWarga($hamil, $ttd, fn ($rutin) => $rutin)),
            new ItemRekap('TTD_TIDAK_RUTIN', 'Bumil Tidak Rutin Konsumsi TTD/MMS', $this->hitungWarga($hamil, $ttd, fn ($rutin) => ! $rutin)),
            new ItemRekap('PMT_MENDAPAT', 'Bumil Mendapat PMT Bumil KEK', $this->hitungWarga($hamil, $pmt, $ya)),
            new ItemRekap('PMT_RUTIN', 'Bumil Konsumsi PMT Bumil KEK Setiap Hari', $this->hitungWarga($hamil, $pmt, fn ($rutin) => $rutin)),
            new ItemRekap('PMT_TIDAK_RUTIN', 'Bumil Tidak Konsumsi Rutin PMT Bumil KEK', $this->hitungWarga($hamil, $pmt, fn ($rutin) => ! $rutin)),
            new ItemRekap('KELAS_IKUT', 'Bumil Ikut Kelas Bumil', $this->hitungWarga($hamil, $kelas, fn ($ikut) => $ikut)),
            new ItemRekap('KELAS_TIDAK', 'Bumil Tidak Ikut Kelas Bumil', $this->hitungWarga($hamil, $kelas, fn ($ikut) => ! $ikut)),
        ];
    }

    // ───────────────────────── Baris bersama ─────────────────────────

    /** @return list<ItemRekap> */
    private function barisImt(Collection $data): array
    {
        $imt = config('posyandu.ambang.imt');
        $status = fn ($p) => $p->pengukuranDasar?->status_imt;
        $normal = $this->angka((float) $imt['normal_mulai']).' – '.$this->angka((float) $imt['gemuk_mulai'] - 0.1);

        return [
            new ItemRekap('IMT_NORMAL', "IMT Normal ({$normal})", $this->hitungWarga($data, $status, fn ($s) => $s === StatusImt::Normal)),
            new ItemRekap('IMT_RISIKO', 'IMT Risiko (< '.$this->angka((float) $imt['normal_mulai']).' / ≥ '.$this->angka((float) $imt['gemuk_mulai']).')',
                $this->hitungWarga($data, $status, fn ($s) => $s !== StatusImt::Normal)),
        ];
    }

    /** @return list<ItemRekap> */
    private function barisLingkarPerut(Collection $data, string $awalan): array
    {
        $lp = config('posyandu.ambang.lingkar_perut');
        $status = fn ($p) => $p->pengukuranDasar?->status_lingkar_perut;

        return [
            new ItemRekap('LP_NORMAL', "{$awalan} Normal (P: ≤ {$lp['perempuan']}, L: ≤ {$lp['laki_laki']})", $this->hitungWarga($data, $status, fn ($s) => $s === StatusRisiko::Normal)),
            new ItemRekap('LP_RISIKO', "{$awalan} Risiko (P: > {$lp['perempuan']}, L: > {$lp['laki_laki']})", $this->hitungWarga($data, $status, fn ($s) => $s === StatusRisiko::Berisiko)),
        ];
    }

    /** @return list<ItemRekap> */
    private function barisTekananDarah(Collection $data, string $awalan): array
    {
        $batas = $this->batasTd();
        $status = fn ($p) => $p->pengukuranDasar?->status_tekanan_darah;

        return [
            new ItemRekap('TD_NORMAL', "{$awalan} Normal (< {$batas} mmHg)", $this->hitungWarga($data, $status, fn ($s) => $s !== StatusTekananDarah::Tinggi)),
            new ItemRekap('TD_RISIKO', "{$awalan} Risiko (≥ {$batas} mmHg)", $this->hitungWarga($data, $status, fn ($s) => $s === StatusTekananDarah::Tinggi)),
        ];
    }

    private function barisTbc(Collection $data): ItemRekap
    {
        $minimal = (int) config('posyandu.ambang.tbc_minimal_gejala');

        return new ItemRekap('TBC_BERGEJALA', "Bergejala TBC (memenuhi {$minimal} gejala)",
            $this->hitungWarga($data, fn ($p) => $p->skriningTbc?->status_gejala_tbc, fn ($s) => $s === StatusRisiko::Berisiko));
    }

    /** @return list<ItemRekap> */
    private function barisSensorik(Collection $data): array
    {
        $mata = fn ($p) => $p->skriningSensorik?->status_penglihatan;
        $telinga = fn ($p) => $p->skriningSensorik?->status_pendengaran;

        return [
            new ItemRekap('PENGLIHATAN_NORMAL', 'Tes Penglihatan Normal', $this->hitungWarga($data, $mata, fn ($s) => $s === StatusSensorik::Normal)),
            new ItemRekap('PENGLIHATAN_RISIKO', 'Tes Penglihatan Risiko', $this->hitungWarga($data, $mata, fn ($s) => $s === StatusSensorik::Gangguan)),
            new ItemRekap('PENDENGARAN_NORMAL', 'Tes Pendengaran Normal', $this->hitungWarga($data, $telinga, fn ($s) => $s === StatusSensorik::Normal)),
            new ItemRekap('PENDENGARAN_RISIKO', 'Tes Pendengaran Risiko', $this->hitungWarga($data, $telinga, fn ($s) => $s === StatusSensorik::Gangguan)),
        ];
    }

    // ───────────────────────── Pengambil data ─────────────────────────

    /**
     * Jumlah warga per kelompok pada tanggal acuan (sasaran).
     *
     * @return array{balita: int, remaja: int, dewasa: int, lansia: int, hamil: int, nifas: int}
     */
    private function sasaran(Periode $periode): array
    {
        $acuan = $this->tanggalAcuan($periode);
        $sampai = fn (KategoriUsia $k) => $k->rentangTanggalLahir($acuan)['sampai'];

        $baris = DB::table('warga')
            ->where('tanggal_lahir', '<=', $acuan->toDateString())
            ->selectRaw(
                "CASE
                    WHEN status_kondisi = 'hamil' THEN 'hamil'
                    WHEN status_kondisi IN ('nifas', 'menyusui') THEN 'nifas'
                    WHEN tanggal_lahir <= ? THEN 'lansia'
                    WHEN tanggal_lahir <= ? THEN 'dewasa'
                    WHEN tanggal_lahir <= ? THEN 'remaja'
                    ELSE 'balita'
                 END as kelompok, COUNT(*) as jumlah",
                [$sampai(KategoriUsia::Lansia), $sampai(KategoriUsia::Dewasa), $sampai(KategoriUsia::Remaja)]
            )
            ->groupBy('kelompok')
            ->pluck('jumlah', 'kelompok');

        return $this->lengkapiKelompok($baris->map(fn ($n) => (int) $n)->all());
    }

    /**
     * Warga yang Hadir di kegiatan bulan itu per kelompok; tiap warga dihitung sekali
     * (kelompok menurut kegiatan terakhir yang dihadirinya).
     *
     * @return array{balita: int, remaja: int, dewasa: int, lansia: int, hamil: int, nifas: int}
     */
    private function datang(Periode $periode): array
    {
        $idKegiatan = $this->kegiatanDalam($periode)->pluck('id_kegiatan');
        if ($idKegiatan->isEmpty()) {
            return $this->lengkapiKelompok([]);
        }

        $kelompokPerWarga = [];
        DB::table('kehadiran as k')
            ->join('warga as w', 'w.nik', '=', 'k.nik')
            ->join('kegiatan as g', 'g.id_kegiatan', '=', 'k.id_kegiatan')
            ->whereIn('k.id_kegiatan', $idKegiatan)
            ->where('k.status_kehadiran', true)
            ->orderBy('g.tanggal_kegiatan')->orderBy('g.id_kegiatan')
            ->select(['k.nik', 'w.tanggal_lahir', 'w.status_kondisi', 'g.tanggal_kegiatan'])
            ->get()
            ->each(function ($baris) use (&$kelompokPerWarga) {
                $kelompokPerWarga[$baris->nik] = $this->kelompok($baris->tanggal_lahir, $baris->status_kondisi, CarbonImmutable::parse($baris->tanggal_kegiatan));
            });

        return $this->lengkapiKelompok(array_count_values($kelompokPerWarga));
    }

    /**
     * Pemeriksaan Submit di kegiatan bulan itu, dikelompokkan per NIK (terbaru lebih dulu).
     *
     * Dipakai juga oleh Dashboard agar cara hitungnya sama (keputusan 52).
     *
     * @param  list<KategoriPemeriksaan>  $kategori
     * @return Collection<string, Collection<int, Pemeriksaan>>
     */
    public function pemeriksaan(Periode $periode, array $kategori): Collection
    {
        $idKegiatan = $this->kegiatanDalam($periode)->pluck('id_kegiatan');
        if ($idKegiatan->isEmpty()) {
            return collect();
        }

        return Pemeriksaan::query()
            ->selesai()
            ->whereIn('id_kegiatan', $idKegiatan)
            ->whereIn('kategori_usia_saat_periksa', array_map(fn ($k) => $k->value, $kategori))
            ->select(['id_pemeriksaan', 'nik', 'id_kegiatan', 'kategori_usia_saat_periksa', 'tanggal_pemeriksaan'])
            ->with([
                'warga:nik,jenis_kelamin', 'warga.riwayatPtm:id_riwayat,nik,id_penyakit',
                'pengukuranDasar', 'skriningTbc', 'skriningSensorik', 'skriningPuma', 'pemeriksaanBumil', 'penyuluhanRujukan',
            ])
            ->orderByDesc('tanggal_pemeriksaan')->orderByDesc('id_pemeriksaan')
            ->get()
            ->groupBy('nik');
    }

    /**
     * Jumlah warga yang indikatornya memenuhi syarat. Nilai indikator tiap warga diambil dari pemeriksaan
     * terbarunya yang memuat indikator itu (null = tidak diperiksa → tidak dihitung).
     *
     * @param  Collection<string, Collection<int, Pemeriksaan>>  $data
     */
    public function hitungWarga(Collection $data, Closure $ambil, Closure $syarat): int
    {
        $jumlah = 0;
        foreach ($data as $daftar) {
            foreach ($daftar as $pemeriksaan) {
                $nilai = $ambil($pemeriksaan);
                if ($nilai !== null) {
                    $jumlah += $syarat($nilai) ? 1 : 0;
                    break;
                }
            }
        }

        return $jumlah;
    }

    private function kelompok(string $tanggalLahir, string $kondisi, CarbonInterface $acuan): string
    {
        return match ($kondisi) {
            'hamil' => 'hamil',
            'nifas', 'menyusui' => 'nifas',
            default => KategoriUsia::dariTanggalLahir($tanggalLahir, $acuan)->value,
        };
    }

    /** @return array{balita: int, remaja: int, dewasa: int, lansia: int, hamil: int, nifas: int} */
    private function lengkapiKelompok(array $hitungan): array
    {
        $hasil = [];
        foreach (['balita', 'remaja', 'dewasa', 'lansia', 'hamil', 'nifas'] as $kunci) {
            $hasil[$kunci] = (int) ($hitungan[$kunci] ?? 0);
        }

        return $hasil;
    }

    /** Mis. Remaja "5–17" dari config kategori_usia. */
    private function rentangUsia(KategoriUsia $kategori): string
    {
        return $kategori->usiaMulai().'–'.($kategori->berikutnya()->usiaMulai() - 1);
    }

    private function batasTd(): string
    {
        $td = config('posyandu.ambang.tekanan_darah');

        return "{$td['sistolik_tinggi']}/{$td['diastolik_tinggi']}";
    }

    /** Angka gaya Indonesia: 18.5 → "18,5", 200.0 → "200". */
    private function angka(float $nilai): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($nilai, 1, '.', ''), '0'), '.'));
    }
}
