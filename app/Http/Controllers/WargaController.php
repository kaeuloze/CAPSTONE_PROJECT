<?php

namespace App\Http\Controllers;

use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Http\Requests\Warga\SimpanWargaRequest;
use App\Models\Penyakit;
use App\Models\Warga;
use App\Services\WargaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Data Warga (SKF-04 & SKF-05; wireframe Gambar 2.43–2.45; Activity Diagram 2.29–2.32).
 */
class WargaController extends Controller
{
    public function __construct(private readonly WargaService $layanan)
    {
    }

    /** Daftar warga dengan pencarian Nama/NIK, filter kategori usia, dan pagination. */
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'in:'.implode(',', KategoriUsia::values())],
        ]);

        $kategori = KategoriUsia::tryFrom($filter['kategori'] ?? '');

        $daftarWarga = Warga::query()
            ->select(['nik', 'nama_warga', 'jenis_kelamin', 'tanggal_lahir', 'status_kondisi'])
            ->cari($filter['cari'] ?? null)
            ->kategori($kategori)
            ->orderBy('nama_warga')
            ->orderBy('nik')
            ->paginate(config('posyandu.warga.per_halaman'))
            ->withQueryString();

        return view('warga.index', [
            'daftarWarga' => $daftarWarga,
            'cari' => $filter['cari'] ?? '',
            'kategoriDipilih' => $kategori,
        ]);
    }

    public function create(): View
    {
        return view('warga.form', [
            'warga' => new Warga(['status_kondisi' => StatusKondisi::Normal]),
            'daftarPenyakit' => $this->daftarPenyakit(),
            'penyakitDipilih' => [],
        ]);
    }

    public function store(SimpanWargaRequest $request): RedirectResponse
    {
        $warga = $this->layanan->tambah($request->dataWarga(), $request->idPenyakit());

        return redirect()->route('warga.index')
            ->with('sukses', "Data warga {$warga->nama_warga} berhasil disimpan.");
    }

    public function show(Warga $warga): View
    {
        return view('warga.show', [
            'warga' => $warga,
            'daftarPenyakit' => $this->daftarPenyakit(),
            'penyakitDipilih' => $warga->riwayatPtm()->pluck('id_penyakit')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function edit(Warga $warga): View
    {
        return view('warga.form', [
            'warga' => $warga,
            'daftarPenyakit' => $this->daftarPenyakit(),
            'penyakitDipilih' => $warga->riwayatPtm()->pluck('id_penyakit')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function update(SimpanWargaRequest $request, Warga $warga): RedirectResponse
    {
        try {
            $warga = $this->layanan->ubah($warga, $request->dataWarga(), $request->idPenyakit(), $request->validated('versi'));
        } catch (DataSudahBerubahException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('warga.index')
            ->with('sukses', "Perubahan data {$warga->nama_warga} berhasil disimpan.");
    }

    public function destroy(Warga $warga): RedirectResponse
    {
        try {
            $this->layanan->hapus($warga);
        } catch (DataMasihDipakaiException $e) {
            return back()->with('galat', $e->getMessage());
        }

        return redirect()->route('warga.index')
            ->with('sukses', "Data warga {$warga->nama_warga} berhasil dihapus.");
    }

    /** Master penyakit untuk centang Riwayat PTM (urut sesuai wireframe). */
    private function daftarPenyakit()
    {
        return Penyakit::query()->select(['id_penyakit', 'nama_penyakit'])->orderBy('id_penyakit')->get();
    }
}
