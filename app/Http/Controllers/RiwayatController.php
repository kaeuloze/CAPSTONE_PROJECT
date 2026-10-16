<?php

namespace App\Http\Controllers;

use App\Enums\KategoriUsia;
use App\Enums\StatusPemeriksaan;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\KatalogForm;
use App\Services\Pemeriksaan\PemeriksaanService;
use App\Services\Pemeriksaan\RiwayatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu Riwayat Pemeriksaan (Figma "Daftar Warga Riwayat Pemeriksaan" 198:723,
 * "Kelola Riwayat Pemeriksaan" 141:921, "Detail Pemeriksaan" 141:3062, "Edit Pemeriksaan" 253:1732).
 */
class RiwayatController extends Controller
{
    public function __construct(
        private readonly RiwayatService $layanan,
        private readonly PemeriksaanService $pemeriksaan,
        private readonly KatalogForm $katalog,
    ) {
    }

    /** Daftar seluruh warga dengan kunjungan terakhir. */
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::in(KategoriUsia::values())],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $cari = $filter['cari'] ?? null;
        $kategori = KategoriUsia::tryFrom($filter['kategori'] ?? '');

        return view('riwayat.index', [
            'daftarWarga' => $this->layanan->daftarWarga($cari, $kategori),
            'cari' => $cari ?? '',
            'kategoriDipilih' => $kategori,
        ]);
    }

    /** Riwayat pemeriksaan satu warga (tombol "Lihat Riwayat"). */
    public function warga(Request $request, Warga $warga): View
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return view('riwayat.warga', [
            'warga' => $warga,
            'daftar' => $this->layanan->riwayatWarga($warga),
            'katalog' => $this->katalog,
        ]);
    }

    /** Detail Pemeriksaan (hanya-baca) + tombol Edit & Hapus. */
    public function show(Pemeriksaan $pemeriksaan): View
    {
        return view('riwayat.detail', $this->dataFormulir($pemeriksaan));
    }

    public function edit(Pemeriksaan $pemeriksaan): View|RedirectResponse
    {
        $data = $this->dataFormulir($pemeriksaan);
        if (! $data['definisi']) {
            return redirect()->route('riwayat.show', $pemeriksaan);
        }

        return view('riwayat.edit', $data);
    }

    /** Tombol "Simpan Perubahan". */
    public function update(Request $request, Pemeriksaan $pemeriksaan): RedirectResponse
    {
        $this->pastikanSelesai($pemeriksaan);
        $definisi = $this->katalog->untuk($pemeriksaan->kategori_usia_saat_periksa);
        abort_unless($definisi, 404);

        $data = $request->validate($definisi->aturanValidasi() + [
            'versi' => ['nullable', 'array'],
            'versi.*' => ['nullable', 'string', 'size:32'],
            'diubah' => ['nullable', 'array'],
            'diubah.*' => ['string', Rule::in(array_keys($definisi->bagian()))],
        ], PemeriksaanController::pesanValidasi());

        try {
            $this->layanan->ubah(
                $pemeriksaan, $definisi,
                $data['bagian'] ?? [], $data['versi'] ?? [],
                $request->has('diubah') ? array_values(array_unique($data['diubah'] ?? [])) : null,
            );
        } catch (AksiDitolakException $e) {
            return redirect()->route('riwayat.warga', $pemeriksaan->nik)->with('galat', $e->getMessage());
        } catch (DataSudahBerubahException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('riwayat.show', $pemeriksaan)->with('sukses', 'Perubahan data pemeriksaan berhasil disimpan.');
    }

    /** Tombol "Hapus" di Detail Pemeriksaan (dengan dialog konfirmasi). */
    public function destroy(Pemeriksaan $pemeriksaan): RedirectResponse
    {
        $this->pastikanSelesai($pemeriksaan);
        $kegiatan = $pemeriksaan->kegiatan;

        try {
            $this->layanan->hapus($pemeriksaan);
        } catch (AksiDitolakException $e) {
            return redirect()->route('riwayat.warga', $pemeriksaan->nik)->with('galat', $e->getMessage());
        }

        $pesan = 'Data pemeriksaan tanggal '.$pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y').' berhasil dihapus.';
        if ($kegiatan->status_kegiatan) {
            $pesan .= ' Karena kegiatannya masih berlangsung, warga ini muncul kembali di menu Pemeriksaan.';
        }

        return redirect()->route('riwayat.warga', $pemeriksaan->nik)->with('sukses', $pesan);
    }

    /** Data bersama halaman Detail & Edit. */
    private function dataFormulir(Pemeriksaan $pemeriksaan): array
    {
        $this->pastikanSelesai($pemeriksaan);
        $pemeriksaan->loadMissing(['warga', 'kegiatan']);
        $warga = $pemeriksaan->warga;
        $definisi = $this->katalog->untuk($pemeriksaan->kategori_usia_saat_periksa);
        [$nilai, $versi] = $definisi ? $this->pemeriksaan->nilaiFormulir($definisi, $pemeriksaan) : [[], []];

        return [
            'pemeriksaan' => $pemeriksaan,
            'warga' => $warga,
            'kegiatan' => $pemeriksaan->kegiatan,
            'kategori' => $pemeriksaan->kategori_usia_saat_periksa,
            'usia' => (int) $warga->tanggal_lahir->diffInYears($pemeriksaan->tanggal_pemeriksaan),
            'definisi' => $definisi,
            'tabNonaktif' => $definisi?->tabNonaktif($warga, $pemeriksaan->tanggal_pemeriksaan) ?? [],
            'nilai' => $nilai,
            'versi' => $versi,
            'jenisDilakukan' => array_map(fn ($j) => $j->value, $pemeriksaan->jenis_pemeriksaan),
        ];
    }

    /** Draft belum masuk Riwayat (keputusan 40). */
    private function pastikanSelesai(Pemeriksaan $pemeriksaan): void
    {
        abort_unless($pemeriksaan->status_pemeriksaan === StatusPemeriksaan::Selesai, 404);
    }
}
