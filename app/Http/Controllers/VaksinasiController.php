<?php

namespace App\Http\Controllers;

use App\Enums\StatusAsi;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Warga;
use App\Services\KegiatanService;
use App\Services\VaksinasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Menu Vaksinasi Balita (SKF-10; Figma "Daftar Vaksinasi Balita" 192:72 & "Vaksinasi Balita" 141:2678).
 */
class VaksinasiController extends Controller
{
    public function __construct(
        private readonly KegiatanService $kegiatanService,
        private readonly VaksinasiService $layanan,
    ) {
    }

    /** Daftar balita yang Hadir pada kegiatan aktif. */
    public function index(Request $request): View
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return view('vaksinasi.kosong');
        }
        $cari = $this->cari($request);

        return view('vaksinasi.index', [
            'kegiatan' => $kegiatan,
            'daftar' => $this->layanan->daftar($kegiatan, $cari),
            'cari' => $cari ?? '',
        ]);
    }

    /** Data terbaru untuk pembaruan otomatis daftar (polling, keputusan 23). */
    public function status(Request $request): JsonResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return response()->json(['aktif' => false])->header('Cache-Control', 'no-store');
        }
        $daftar = $this->layanan->daftar($kegiatan, $this->cari($request));

        return response()->json([
            'aktif' => true,
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'total' => $daftar->total(),
            'baris' => $daftar->getCollection()->map(fn (Warga $w) => [
                'nama' => $w->nama_warga,
                'nik' => $w->nik,
                'url' => route('vaksinasi.form', $w->nik),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    /** Halaman checklist vaksin per dosis + ASI (tombol "Buka"). */
    public function form(Warga $warga): View|RedirectResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return redirect()->route('vaksinasi.index');
        }
        if (($tolak = $this->alasanTolak($kegiatan, $warga)) !== null) {
            return redirect()->route('vaksinasi.index')->with('galat', $tolak);
        }

        [$vaksinasi, $asi] = $this->layanan->catatan($warga);

        return view('vaksinasi.form', [
            'kegiatan' => $kegiatan,
            'warga' => $warga,
            'usiaBulan' => $this->layanan->usiaBulan($warga, $kegiatan->tanggal_kegiatan),
            'daftarVaksin' => $this->layanan->daftarVaksin(),
            'vaksinasi' => $vaksinasi,
            'tampilAsi' => $this->layanan->tampilAsi($warga, $kegiatan->tanggal_kegiatan),
            'bulanAsi' => $this->layanan->bulanAsi(),
            'asi' => $asi,
        ]);
    }

    public function simpan(Request $request, Warga $warga): RedirectResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return redirect()->route('vaksinasi.index')->with('galat', 'Kegiatan sudah selesai sehingga catatan vaksinasi tidak bisa disimpan.');
        }

        $data = $request->validate([
            'vaksin' => ['nullable', 'array'],
            'vaksin.*.sudah' => ['nullable', 'boolean'],
            'vaksin.*.tanggal' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$warga->tanggal_lahir->toDateString(), 'before_or_equal:'.$kegiatan->tanggal_kegiatan->toDateString()],
            'asi' => ['nullable', 'array'],
            'asi.*' => ['nullable', Rule::in(StatusAsi::values())],
            'awal' => ['nullable', 'array'],
            'awal.vaksin' => ['nullable', 'array'],
            'awal.vaksin.*' => ['nullable', 'string', 'max:20'],
            'awal.asi' => ['nullable', 'array'],
            'awal.asi.*' => ['nullable', 'string', 'max:20'],
        ], [
            'vaksin.*.tanggal.date_format' => 'Format tanggal tidak valid.',
            'vaksin.*.tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum tanggal lahir balita.',
            'vaksin.*.tanggal.before_or_equal' => 'Tanggal tidak boleh setelah tanggal kegiatan.',
            'asi.*.in' => 'Pilihan ASI tidak valid.',
        ]);

        // Dosis yang dicentang wajib bertanggal (tanggal di Buku KIA).
        $kurang = [];
        foreach ($data['vaksin'] ?? [] as $id => $isian) {
            if (filter_var($isian['sudah'] ?? false, FILTER_VALIDATE_BOOLEAN) && blank($isian['tanggal'] ?? null)) {
                $kurang["vaksin.{$id}.tanggal"] = 'Isi tanggal pemberian sesuai Buku KIA.';
            }
        }
        if ($kurang !== []) {
            throw ValidationException::withMessages($kurang);
        }

        try {
            $berubah = $this->layanan->simpan($kegiatan, $warga, $data['vaksin'] ?? [], $data['asi'] ?? [], $data['awal'] ?? []);
        } catch (AksiDitolakException $e) {
            return redirect()->route('vaksinasi.index')->with('galat', $e->getMessage());
        } catch (DataSudahBerubahException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('vaksinasi.index')->with('sukses', $berubah > 0
            ? "Catatan vaksinasi {$warga->nama_warga} berhasil disimpan ({$berubah} perubahan)."
            : "Tidak ada perubahan pada catatan vaksinasi {$warga->nama_warga}.");
    }

    private function alasanTolak($kegiatan, Warga $warga): ?string
    {
        if (! $this->layanan->hadir($kegiatan, $warga)) {
            return "{$warga->nama_warga} belum tercatat Hadir. Tandai kehadiran di menu Presensi terlebih dahulu.";
        }
        if (! $this->layanan->balita($warga, $kegiatan->tanggal_kegiatan)) {
            return "{$warga->nama_warga} bukan balita. Pemeriksaannya dilakukan di menu Pemeriksaan.";
        }

        return null;
    }

    private function cari(Request $request): ?string
    {
        return $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ])['cari'] ?? null;
    }
}
