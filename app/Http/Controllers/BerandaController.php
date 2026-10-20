<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\Rekapitulasi\Periode;
use App\Services\Rekapitulasi\RekapitulasiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Halaman publik / Profil Posyandu (SKF-01; Figma 141:62 Landing Page; proposal revisi Gambar 2.38).
 * Tanpa login. Statistik agregat (tanpa identitas) memakai grafik & cara hitung yang SAMA dengan Dashboard,
 * tanpa pembaruan otomatis & tanpa Ekspor Excel; angka di-cache sementara (disetujui 21 Okt 2026).
 */
class BerandaController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly RekapitulasiService $rekap,
    ) {
    }

    public function index(Request $request): View
    {
        [$periode, $kategori] = $this->filter($request);

        $kunci = 'beranda:grafik:'.$periode->kode().':'.$kategori;
        $statistik = Cache::remember($kunci, (int) config('posyandu.publik.cache_detik'), function () use ($periode, $kategori) {
            $data = $this->dashboard->data($periode, $kategori);

            // Yang disimpan hanya angka & label grafik (tidak ada data warga).
            return ['grafik' => $data['grafik'], 'ada_kegiatan' => $data['kegiatan']->isNotEmpty()];
        });

        return view('beranda', [
            'profil' => config('posyandu.profil'),
            'periode' => $periode,
            'filterImt' => $kategori,
            'statistik' => $statistik,
            'daftarTahun' => $this->dashboard->daftarTahun(),
            'kegiatanTerakhir' => $this->rekap->tanggalKegiatanTerakhir(),
        ]);
    }

    /**
     * Isian filter yang tidak valid diabaikan (kembali ke bawaan), karena halaman publik tidak menampilkan pesan galat.
     *
     * @return array{0: Periode, 1: string}
     */
    private function filter(Request $request): array
    {
        $v = Validator::make($request->only('bulan', 'tahun', 'kategori'), [
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,'.CarbonImmutable::today()->format('Y')],
        ]);
        $periode = $v->fails() ? $this->rekap->periodeBawaan() : new Periode((int) $request->input('bulan'), (int) $request->input('tahun'));
        if ($periode->setelahBulanIni()) {
            $periode = $this->rekap->periodeBawaan();
        }

        $kategori = Validator::make($request->only('kategori'), ['kategori' => ['required', Rule::in(array_keys(DashboardService::FILTER_IMT))]])->fails()
            ? 'semua' : (string) $request->input('kategori');

        return [$periode, $kategori];
    }
}
