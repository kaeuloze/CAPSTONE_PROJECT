<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\EksporDashboardExcel;
use App\Services\Rekapitulasi\Periode;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dashboard (SKF-03): tab Bulan Ini (Figma 409:55) & Arsip Bulan Sebelumnya (Figma 409:185), Activity Diagram 2.4.
 * Query: ?tab=arsip&bulan=&tahun= (arsip), ?kategori= (Filter Kategori grafik IMT).
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $layanan,
        private readonly EksporDashboardExcel $ekspor,
    ) {
    }

    public function index(Request $request): View
    {
        [$tab, $periode, $kategori] = $this->filter($request);

        return view('dashboard', [
            'tab' => $tab,
            'data' => $this->layanan->data($periode, $kategori),
            'bulanIni' => $this->layanan->bulanIni(),
            'daftarTahun' => $this->layanan->daftarTahun(),
        ]);
    }

    /** Polling realtime tab Bulan Ini (keputusan 23): hanya angka grafik. */
    public function data(Request $request): JsonResponse
    {
        [, $periode, $kategori] = $this->filter($request);
        $data = $this->layanan->data($periode, $kategori);

        return response()->json([
            'periode' => $periode->kode(),
            'ada_kegiatan' => $data['kegiatan']->isNotEmpty(),
            'grafik' => $this->layanan->angka($data),
        ])->header('Cache-Control', 'no-store');
    }

    public function ekspor(Request $request): StreamedResponse
    {
        [, $periode, $kategori] = $this->filter($request);
        $data = $this->layanan->data($periode, $kategori);
        $buku = $this->ekspor->buat($data);

        return response()->streamDownload(
            fn () => $this->ekspor->tulis($buku),
            EksporDashboardExcel::namaFile($data),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store'],
        );
    }

    /** @return array{0: string, 1: Periode, 2: string} tab, periode, filter kategori IMT */
    private function filter(Request $request): array
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['bulan_ini', 'arsip'])],
            'bulan' => ['nullable', 'required_with:tahun', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'required_with:bulan', 'integer', 'between:2000,'.CarbonImmutable::today()->format('Y')],
            'kategori' => ['nullable', Rule::in(array_keys(DashboardService::FILTER_IMT))],
        ], [
            'required_with' => 'Pilih bulan dan tahun.',
            'between' => 'Periode tidak valid.',
            'integer' => 'Periode tidak valid.',
            'in' => 'Pilihan tidak valid.',
        ]);

        $tab = $data['tab'] ?? 'bulan_ini';
        $kategori = $data['kategori'] ?? 'semua';
        if ($tab === 'bulan_ini') {
            return [$tab, $this->layanan->bulanIni(), $kategori];
        }

        $periode = isset($data['bulan']) ? new Periode((int) $data['bulan'], (int) $data['tahun']) : $this->layanan->bulanLalu();
        // Arsip hanya untuk bulan sebelum bulan ini; bulan berjalan ada di tab Bulan Ini.
        if ($periode->awal()->greaterThanOrEqualTo(CarbonImmutable::today()->startOfMonth())) {
            throw ValidationException::withMessages(['bulan' => 'Arsip hanya untuk bulan sebelumnya. Bulan ini ada di tab Bulan Ini.']);
        }

        return [$tab, $periode, $kategori];
    }
}
