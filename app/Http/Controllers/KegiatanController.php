<?php

namespace App\Http\Controllers;

use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Http\Requests\Kegiatan\SimpanKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\Warga;
use App\Services\KegiatanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Kegiatan (SKF-06; wireframe 2.39–2.41; Activity Diagram 2.5, 2.6, 2.8, 2.9; revisi no. 7).
 */
class KegiatanController extends Controller
{
    public function __construct(private readonly KegiatanService $layanan)
    {
    }

    /** Daftar kegiatan dalam tab Berlangsung / Arsip. */
    public function index(Request $request): View
    {
        $this->layanan->tutupYangKedaluwarsa();

        $tab = $request->query('tab') === 'arsip' ? 'arsip' : 'berlangsung';

        $daftar = Kegiatan::query()
            ->select(['id_kegiatan', 'nama_kegiatan', 'tanggal_kegiatan', 'status_kegiatan', 'waktu_dibuka'])
            ->when($tab === 'arsip', fn ($q) => $q->selesai(), fn ($q) => $q->berlangsung())
            ->withCount([
                'kehadiran as jumlah_hadir' => fn ($q) => $q->where('status_kehadiran', true),
                'kehadiran as jumlah_tercatat',
            ])
            ->orderByDesc('tanggal_kegiatan')
            ->orderByDesc('id_kegiatan')
            ->paginate(config('posyandu.kegiatan.per_halaman'))
            ->withQueryString();

        return view('kegiatan.index', [
            'daftar' => $daftar,
            'tab' => $tab,
            'totalWarga' => Warga::query()->count(), // total untuk kegiatan yang masih berlangsung
            'adaYangBerlangsung' => Kegiatan::query()->berlangsung()->exists(),
        ]);
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
