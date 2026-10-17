<?php

namespace App\Http\Controllers;

use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Http\Requests\Kegiatan\SimpanKegiatanRequest;
use App\Models\Kegiatan;
use App\Services\KegiatanService;
use App\Services\PresensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Kegiatan (SKF-06; wireframe 2.39–2.41; Activity Diagram 2.5, 2.6, 2.8, 2.9; revisi no. 7).
 */
class KegiatanController extends Controller
{
    public function __construct(
        private readonly KegiatanService $layanan,
        private readonly PresensiService $presensi,
    ) {
    }

    /**
     * Daftar kegiatan (Figma "Daftar Kegiatan — hasil rev"):
     * - Tab Berlangsung: satu kartu kegiatan aktif + ringkasan & kehadiran per kategori.
     * - Tab Arsip: tabel kegiatan selesai dengan pencarian nama & filter tahun.
     */
    public function index(Request $request): View
    {
        $this->layanan->tutupYangKedaluwarsa();

        $tab = $request->query('tab') === 'arsip' ? 'arsip' : 'berlangsung';
        $data = [
            'tab' => $tab,
            'jumlahArsip' => Kegiatan::query()->selesai()->count(),
        ];

        if ($tab === 'berlangsung') {
            $aktif = Kegiatan::query()->berlangsung()->latest('waktu_dibuka')->first();
            $data['aktif'] = $aktif;
            if ($aktif) {
                $data['ringkasan'] = $this->presensi->ringkasan($aktif, null, null);
                $data['perKelompok'] = $this->presensi->ringkasanPerKelompok($aktif);
                $data['belumSubmit'] = $aktif->pemeriksaan()->draft()->count();
            }

            return view('kegiatan.index', $data);
        }

        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $cari = trim((string) ($filter['cari'] ?? ''));

        $data['daftar'] = Kegiatan::query()
            ->select(['id_kegiatan', 'nama_kegiatan', 'tanggal_kegiatan', 'status_kegiatan'])
            ->selesai()
            ->when($cari !== '', fn ($q) => $q->where('nama_kegiatan', 'like', '%'.addcslashes($cari, '\\%_').'%'))
            ->when($filter['tahun'] ?? null, fn ($q, $tahun) => $q->whereYear('tanggal_kegiatan', $tahun))
            ->withCount([
                'kehadiran as jumlah_hadir' => fn ($q) => $q->where('status_kehadiran', true),
                'kehadiran as jumlah_tercatat',
            ])
            ->orderByDesc('tanggal_kegiatan')
            ->orderByDesc('id_kegiatan')
            ->paginate(config('posyandu.kegiatan.per_halaman'))
            ->withQueryString();
        $data['cari'] = $cari;
        $data['tahunDipilih'] = $filter['tahun'] ?? null;
        $data['daftarTahun'] = Kegiatan::query()->selesai()
            ->selectRaw('DISTINCT YEAR(tanggal_kegiatan) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('kegiatan.index', $data);
    }

    public function create(): View
    {
        return view('kegiatan.create', ['kegiatanAktif' => $this->layanan->kegiatanAktif()]);
    }

    public function store(SimpanKegiatanRequest $request): RedirectResponse
    {
        try {
            $kegiatan = $this->layanan->buat($request->dataKegiatan());
        } catch (AksiDitolakException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('kegiatan.index')
            ->with('sukses', "Kegiatan {$kegiatan->nama_kegiatan} dibuat dan sedang berlangsung.");
    }

    /** Detail & Edit Kegiatan (wireframe 2.41). */
    public function edit(Kegiatan $kegiatan): View
    {
        $this->layanan->tutupYangKedaluwarsa();

        return view('kegiatan.edit', ['kegiatan' => $kegiatan->refresh()]);
    }

    public function update(SimpanKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        try {
            $kegiatan = $this->layanan->ubah($kegiatan, $request->dataKegiatan(), $request->validated('versi'));
        } catch (DataSudahBerubahException|AksiDitolakException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('kegiatan.index', ['tab' => $kegiatan->status_kegiatan ? 'berlangsung' : 'arsip'])
            ->with('sukses', "Perubahan kegiatan {$kegiatan->nama_kegiatan} berhasil disimpan.");
    }

    public function destroy(Kegiatan $kegiatan): RedirectResponse
    {
        try {
            $this->layanan->hapus($kegiatan);
        } catch (DataMasihDipakaiException $e) {
            return back()->with('galat', $e->getMessage());
        }

        return redirect()->route('kegiatan.index', ['tab' => $kegiatan->status_kegiatan ? 'berlangsung' : 'arsip'])
            ->with('sukses', "Kegiatan {$kegiatan->nama_kegiatan} berhasil dihapus.");
    }

    /** Tombol "Selesaikan Kegiatan" (revisi): sisa warga otomatis Tidak Hadir. */
    public function selesaikan(Kegiatan $kegiatan): RedirectResponse
    {
        $this->layanan->selesaikan($kegiatan);

        return redirect()->route('kegiatan.index', ['tab' => 'arsip'])
            ->with('sukses', "Kegiatan {$kegiatan->nama_kegiatan} telah diselesaikan. Warga yang belum hadir dicatat Tidak Hadir.");
    }
}
