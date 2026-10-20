<?php

namespace App\Services;

use App\Enums\KategoriPemeriksaan;
use App\Enums\StatusImt;
use App\Enums\StatusRisiko;
use App\Enums\StatusTekananDarah;
use App\Models\Pemeriksaan;
use App\Models\Penyakit;
use App\Services\Rekapitulasi\PenghitungRekap;
use App\Services\Rekapitulasi\Periode;
use Carbon\CarbonImmutable;

/**
 * Dashboard (SKF-03; Figma 409:55 Bulan Ini & 409:185 Arsip Bulan Sebelumnya; proposal revisi Gambar 2.40–2.41).
 * Grafik = jumlah (count) warga per kategori hasil ploting, bukan rata-rata. Cara hitung sama dengan
 * Rekapitulasi: hanya pemeriksaan Submit, tiap warga dihitung sekali per bulan (disetujui 21 Okt 2026).
 */
class DashboardService
{
    /** Pilihan "Filter Kategori" grafik IMT. */
    public const FILTER_IMT = [
        'semua' => 'Semua Kategori',
        'remaja' => 'Remaja',
        'dewasa' => 'Dewasa',
        'lansia' => 'Lansia',
        'ibu' => 'Ibu Hamil / Nifas',
    ];

    public function __construct(private readonly PenghitungRekap $penghitung)
    {
    }

    public function bulanIni(): Periode
    {
        return Periode::dariTanggal(CarbonImmutable::today());
    }

    /** Pilihan tahun di tab Arsip: tahun kegiatan pertama s.d. tahun ini (terbaru dulu). */
    public function daftarTahun(): array
    {
        $tahunIni = (int) CarbonImmutable::today()->format('Y');
        $pertama = \App\Models\Kegiatan::query()->min('tanggal_kegiatan');
        $mulai = $pertama ? min((int) CarbonImmutable::parse($pertama)->format('Y'), $tahunIni) : $tahunIni;

        return range($tahunIni, $mulai);
    }

    public function bulanLalu(): Periode
    {
        return Periode::dariTanggal(CarbonImmutable::today()->startOfMonth()->subMonth());
    }

    /**
     * @return array{periode: Periode, kegiatan: \Illuminate\Support\Collection, filter_imt: string, grafik: array<string, array{judul: string, keterangan: string, batang: list<array{kunci: string, label: string, nilai: int}>}>}
     */
    public function data(Periode $periode, string $filterImt = 'semua'): array
    {
        $filterImt = array_key_exists($filterImt, self::FILTER_IMT) ? $filterImt : 'semua';
        $semua = $this->penghitung->pemeriksaan($periode, KategoriPemeriksaan::cases());

        return [
            'periode' => $periode,
            'kegiatan' => $this->penghitung->kegiatanDalam($periode),
            'filter_imt' => $filterImt,
            'grafik' => [
                'imt' => $this->grafikImt($semua, $filterImt),
                'ptm' => $this->grafikPtm($semua),
                'td' => $this->grafikTekananDarah($semua),
                'tbc' => $this->grafikTbc($semua),
            ],
        ];
    }

    /** Hanya angka (untuk polling realtime & uji). @return array<string, array<string, int>> */
    public function angka(array $data): array
    {
        return array_map(fn ($g) => array_column($g['batang'], 'nilai', 'kunci'), $data['grafik']);
    }

    private function grafikImt($semua, string $filter): array
    {
        $imt = config('posyandu.ambang.imt');
        $angka = fn (float $n) => str_replace('.', ',', rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.'));
        $data = $semua->map(fn ($daftar) => $daftar->filter(fn (Pemeriksaan $p) => $this->cocokFilter($p, $filter))->values())
            ->filter->isNotEmpty();
        // Remaja/Dewasa/Lansia: status IMT dihitung; Ibu Hamil/Nifas: ploting IMT sesuai kurva Buku KIA.
        $status = fn (Pemeriksaan $p) => $this->ibu($p) ? $p->pemeriksaanBumil?->status_ploting_imt : $p->pengukuranDasar?->status_imt;

        return [
            'judul' => 'Distribusi Status Gizi (IMT)',
            'keterangan' => 'Jumlah warga per kategori ploting IMT (bukan rata-rata nilai)',
            'batang' => [
                ['kunci' => 'normal', 'label' => 'Normal ('.$angka((float) $imt['normal_mulai']).'–'.$angka((float) $imt['gemuk_mulai'] - 0.1).')',
                    'nilai' => $this->penghitung->hitungWarga($data, $status, fn ($s) => $s === StatusImt::Normal)],
                ['kunci' => 'kurus', 'label' => 'Risiko Kurus (<'.$angka((float) $imt['normal_mulai']).')',
                    'nilai' => $this->penghitung->hitungWarga($data, $status, fn ($s) => $s === StatusImt::Kurus)],
                ['kunci' => 'gemuk', 'label' => 'Risiko Gemuk (≥'.$angka((float) $imt['gemuk_mulai']).')',
                    'nilai' => $this->penghitung->hitungWarga($data, $status, fn ($s) => in_array($s, [StatusImt::Gemuk, StatusImt::Obesitas], true))],
            ],
        ];
    }

    /** Warga yang diperiksa (Submit) bulan itu dan punya riwayat penyakit tersebut di Data Warga (disetujui). */
    private function grafikPtm($semua): array
    {
        $penyakitPerWarga = $semua->map(fn ($daftar) => $daftar->first()->warga->riwayatPtm->pluck('id_penyakit')->all());

        $batang = Penyakit::query()->select(['id_penyakit', 'nama_penyakit'])->orderBy('id_penyakit')->get()
            ->map(fn (Penyakit $p) => [
                'kunci' => (string) $p->id_penyakit,
                'label' => $p->nama_penyakit,
                'nilai' => $penyakitPerWarga->filter(fn ($ids) => in_array($p->id_penyakit, $ids))->count(),
            ])->all();

        return [
            'judul' => 'Grafik Risiko PTM',
            'keterangan' => 'Jumlah warga yang diperiksa pada periode ini dengan riwayat/perilaku berisiko — sumber: Kartu Bantu',
            'batang' => $batang,
        ];
    }

    /** Hanya Dewasa & Lansia (Figma: "dari form pemeriksaan dewasa/lansia"). */
    private function grafikTekananDarah($semua): array
    {
        $td = config('posyandu.ambang.tekanan_darah');
        $batas = "{$td['sistolik_tinggi']}/{$td['diastolik_tinggi']}";
        $data = $semua->map(fn ($daftar) => $daftar->filter(fn (Pemeriksaan $p) => in_array($p->kategori_usia_saat_periksa, [KategoriPemeriksaan::Dewasa, KategoriPemeriksaan::Lansia], true))->values())
            ->filter->isNotEmpty();
        $status = fn (Pemeriksaan $p) => $p->pengukuranDasar?->status_tekanan_darah;

        return [
            'judul' => 'Distribusi Tekanan Darah',
            'keterangan' => 'Jumlah warga per ploting TD (dari form pemeriksaan dewasa/lansia)',
            'batang' => [
                ['kunci' => 'normal', 'label' => "Normal (<{$batas})", 'nilai' => $this->penghitung->hitungWarga($data, $status, fn ($s) => $s !== StatusTekananDarah::Tinggi)],
                ['kunci' => 'risiko', 'label' => "Risiko (≥{$batas})", 'nilai' => $this->penghitung->hitungWarga($data, $status, fn ($s) => $s === StatusTekananDarah::Tinggi)],
            ],
        ];
    }

    /** Semua kategori yang diskrining TBC. */
    private function grafikTbc($semua): array
    {
        $minimal = (int) config('posyandu.ambang.tbc_minimal_gejala');
        $status = fn (Pemeriksaan $p) => $p->skriningTbc?->status_gejala_tbc;

        return [
            'judul' => 'Skrining TBC',
            'keterangan' => "Jumlah warga bergejala TBC (≥ {$minimal} gejala → dirujuk ke Puskesmas)",
            'batang' => [
                ['kunci' => 'tidak', 'label' => 'Tidak Bergejala', 'nilai' => $this->penghitung->hitungWarga($semua, $status, fn ($s) => $s !== StatusRisiko::Berisiko)],
                ['kunci' => 'bergejala', 'label' => 'Bergejala TBC', 'nilai' => $this->penghitung->hitungWarga($semua, $status, fn ($s) => $s === StatusRisiko::Berisiko)],
            ],
        ];
    }

    private function cocokFilter(Pemeriksaan $p, string $filter): bool
    {
        return match ($filter) {
            'semua' => true,
            'ibu' => $this->ibu($p),
            default => $p->kategori_usia_saat_periksa->value === $filter,
        };
    }

    private function ibu(Pemeriksaan $p): bool
    {
        return in_array($p->kategori_usia_saat_periksa, [KategoriPemeriksaan::IbuHamil, KategoriPemeriksaan::IbuNifas], true);
    }
}
