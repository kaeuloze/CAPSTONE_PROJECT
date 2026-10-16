<?php

namespace App\Http\Controllers;

use App\Enums\KategoriUsia;
use App\Exceptions\AksiDitolakException;
use App\Models\Kegiatan;
use App\Models\Warga;
use App\Services\KegiatanService;
use App\Services\PresensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Presensi / Detail Kegiatan dan Presensi (wireframe 2.42; Activity Diagram 2.7).
 * Realtime antar-meja memakai polling ke route presensi.status (keputusan 23).
 */
class PresensiController extends Controller
{
    public function __construct(
        private readonly KegiatanService $kegiatanService,
        private readonly PresensiService $presensiService,
    ) {
    }

    public function index(Request $request, Kegiatan $kegiatan): View
    {
        $this->kegiatanService->tutupYangKedaluwarsa();
        $kegiatan->refresh();
        [$cari, $kategori] = $this->filter($request);

        return view('presensi.index', [
            'kegiatan' => $kegiatan,
            'daftar' => $this->presensiService->daftar($kegiatan, $cari, $kategori),
            'ringkasan' => $this->presensiService->ringkasan($kegiatan, $cari, $kategori),
            'cari' => $cari ?? '',
            'kategoriDipilih' => $kategori,
            'belumSubmit' => $kegiatan->status_kegiatan ? $kegiatan->pemeriksaan()->draft()->count() : 0,
        ]);
    }

    /** Data terbaru untuk polling (status warga di halaman yang sedang dibuka + ringkasan). */
    public function status(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $this->kegiatanService->tutupYangKedaluwarsa();
        $kegiatan->refresh();
        [$cari, $kategori] = $this->filter($request);

        $daftar = $this->presensiService->daftar($kegiatan, $cari, $kategori);

        return response()->json([
            'berlangsung' => $kegiatan->status_kegiatan,
            'ringkasan' => $this->presensiService->ringkasan($kegiatan, $cari, $kategori),
            'kehadiran' => $daftar->getCollection()->mapWithKeys(fn ($w) => [$w->nik => (bool) $w->hadir]),
        ])->header('Cache-Control', 'no-store');
    }

    /** Tandai Hadir / Batalkan — langsung tersimpan setiap klik (disetujui). */
    public function atur(Request $request, Kegiatan $kegiatan, Warga $warga): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['hadir' => ['required', 'in:0,1']], ['hadir.*' => 'Aksi presensi tidak valid.']);
        $hadir = $data['hadir'] === '1';

        try {
            $this->kegiatanService->aturKehadiran($kegiatan, $warga, $hadir);
        } catch (AksiDitolakException $e) {
            return $request->expectsJson()
                ? response()->json(['pesan' => $e->getMessage()], 422)
                : back()->with('galat', $e->getMessage());
        }

        $pesan = $hadir ? "{$warga->nama_warga} ditandai Hadir." : "Kehadiran {$warga->nama_warga} dibatalkan.";

        return $request->expectsJson()
            ? response()->json(['pesan' => $pesan, 'nik' => $warga->nik, 'hadir' => $hadir])
            : back()->with('sukses', $pesan);
    }

    /** @return array{0: ?string, 1: ?KategoriUsia} */
    private function filter(Request $request): array
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'in:'.implode(',', KategoriUsia::values())],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [$filter['cari'] ?? null, KategoriUsia::tryFrom($filter['kategori'] ?? '')];
    }
}
