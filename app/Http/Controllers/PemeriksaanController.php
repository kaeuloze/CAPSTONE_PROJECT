<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPemeriksaan;
use App\Enums\StatusPemeriksaan;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Kegiatan;
use App\Models\Warga;
use App\Services\KegiatanService;
use App\Services\Pemeriksaan\KatalogForm;
use App\Services\Pemeriksaan\PemeriksaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu Pemeriksaan (SKF-07 & SKF-08; Figma "Pemeriksaan — Tanpa Kolom Status", "Tidak Ada Kegiatan Aktif",
 * dan form "Wajib & Simpan/Submit (hasil rev)").
 */
class PemeriksaanController extends Controller
{
    public function __construct(
        private readonly KegiatanService $kegiatanService,
        private readonly PemeriksaanService $layanan,
        private readonly KatalogForm $katalog,
    ) {
    }

    /** Daftar warga hadir yang siap / sedang diperiksa pada kegiatan aktif. */
    public function index(Request $request): View
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return view('pemeriksaan.kosong');
        }

        [$cari, $kategori] = $this->filter($request);

        return view('pemeriksaan.index', [
            'kegiatan' => $kegiatan,
            'daftar' => $this->layanan->antrean($kegiatan, $cari, $kategori),
            'cari' => $cari ?? '',
            'kategoriDipilih' => $kategori,
        ]);
    }

    /** Data terbaru untuk pembaruan otomatis daftar (polling, keputusan 23). */
    public function status(Request $request): JsonResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return response()->json(['aktif' => false])->header('Cache-Control', 'no-store');
        }

        [$cari, $kategori] = $this->filter($request);
        $daftar = $this->layanan->antrean($kegiatan, $cari, $kategori);

        return response()->json([
            'aktif' => true,
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'total' => $daftar->total(),
            'baris' => $daftar->getCollection()->values()->map(fn ($w, $i) => [
                'no' => $daftar->firstItem() + $i,
                'nik' => $w->nik,
                'nama' => $w->nama_warga,
                'kategori' => KategoriPemeriksaan::from($w->kategori_form)->label(),
                'draft' => $w->status_pemeriksaan === StatusPemeriksaan::Draft->value,
                'url' => route('pemeriksaan.form', $w->nik),
            ]),
        ])->header('Cache-Control', 'no-store');
    }

    /** Formulir pemeriksaan sesuai kategori warga ("Mulai Pemeriksaan" / "Pemeriksaan Belum Selesai"). */
    public function form(Warga $warga): View|RedirectResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return redirect()->route('pemeriksaan.index');
        }
        if (! $this->layanan->hadir($kegiatan, $warga)) {
            return redirect()->route('pemeriksaan.index')
                ->with('galat', "{$warga->nama_warga} belum tercatat Hadir. Tandai kehadiran di menu Presensi terlebih dahulu.");
        }

        $pemeriksaan = $this->layanan->pemeriksaanDari($kegiatan, $warga);
        if ($pemeriksaan?->status_pemeriksaan === StatusPemeriksaan::Selesai) {
            return redirect()->route('pemeriksaan.index')
                ->with('galat', "Pemeriksaan {$warga->nama_warga} sudah di-Submit. Koreksi dilakukan di menu Riwayat Pemeriksaan.");
        }

        $kategori = $pemeriksaan?->kategori_usia_saat_periksa ?? $this->layanan->kategoriUntuk($warga, $kegiatan->tanggal_kegiatan);
        if (! $kategori) {
            return redirect()->route('pemeriksaan.index')
                ->with('galat', 'Balita tidak diperiksa di menu ini. Gunakan menu Vaksinasi Balita.');
        }

        $definisi = $this->katalog->untuk($kategori);
        $data = [
            'kegiatan' => $kegiatan,
            'warga' => $warga,
            'kategori' => $kategori,
            'usia' => (int) $warga->tanggal_lahir->diffInYears($kegiatan->tanggal_kegiatan),
        ];
        if (! $definisi) {
            return view('pemeriksaan.belum-tersedia', $data);
        }

        [$nilai, $versi] = $this->layanan->nilaiFormulir($definisi, $pemeriksaan);

        return view('pemeriksaan.form', $data + [
            'definisi' => $definisi,
            'tabNonaktif' => $definisi->tabNonaktif($warga, $kegiatan->tanggal_kegiatan),
            'peringatan' => $this->layanan->peringatanInterval($definisi, $warga, $kegiatan),
            'nilai' => $nilai,
            'versi' => $versi,
            'sudahAdaDraft' => $pemeriksaan !== null,
        ]);
    }

    /** Tombol Simpan (draft) dan Submit (selesai) — berlaku untuk seluruh tab. */
    public function simpan(Request $request, Warga $warga): RedirectResponse
    {
        $kegiatan = $this->kegiatanService->kegiatanAktif();
        if (! $kegiatan) {
            return redirect()->route('pemeriksaan.index')
                ->with('galat', 'Kegiatan sudah selesai sehingga pemeriksaan tidak bisa disimpan. Buka kembali kegiatan dari Arsip untuk melanjutkan.');
        }

        $pemeriksaan = $this->layanan->pemeriksaanDari($kegiatan, $warga);
        $kategori = $pemeriksaan?->kategori_usia_saat_periksa ?? $this->layanan->kategoriUntuk($warga, $kegiatan->tanggal_kegiatan);
        $definisi = $kategori ? $this->katalog->untuk($kategori) : null;
        if (! $definisi) {
            return redirect()->route('pemeriksaan.index')->with('galat', 'Formulir untuk warga ini belum tersedia.');
        }

        $data = $request->validate($definisi->aturanValidasi() + [
            'aksi' => ['required', Rule::in(['simpan', 'submit'])],
            'versi' => ['nullable', 'array'],
            'versi.*' => ['nullable', 'string', 'size:32'],
            'diubah' => ['nullable', 'array'],
            'diubah.*' => ['string', Rule::in(array_keys($definisi->bagian()))],
        ], self::pesanValidasi());

        $submit = $data['aksi'] === 'submit';
        try {
            $this->layanan->simpan(
                $kegiatan, $warga, $definisi,
                $data['bagian'] ?? [], $data['versi'] ?? [],
                $request->has('diubah') ? array_values(array_unique($data['diubah'] ?? [])) : null,
                $submit,
            );
        } catch (AksiDitolakException|DataSudahBerubahException $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->route('pemeriksaan.index')->with('sukses', $submit
            ? "Pemeriksaan {$warga->nama_warga} berhasil di-Submit dan masuk ke Riwayat Pemeriksaan."
            : "Pemeriksaan {$warga->nama_warga} tersimpan sementara (belum di-Submit).");
    }

    /** @return array{0: ?string, 1: ?KategoriPemeriksaan} */
    private function filter(Request $request): array
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::in(KategoriPemeriksaan::values())],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [$filter['cari'] ?? null, KategoriPemeriksaan::tryFrom($filter['kategori'] ?? '')];
    }

    /** Pesan validasi isian formulir pemeriksaan (dipakai juga Edit Riwayat). */
    public static function pesanValidasi(): array
    {
        return [
            'numeric' => 'Isian harus berupa angka.',
            'integer' => 'Isian harus berupa bilangan bulat.',
            'between' => 'Isian harus di antara :min dan :max.',
            'max' => 'Isian terlalu panjang (maksimal :max).',
            'in' => 'Pilihan tidak valid.',
            'date_format' => 'Format tanggal tidak valid.',
            'regex' => 'Format isian tidak valid.',
            'aksi.required' => 'Pilih Simpan atau Submit.',
        ];
    }
}
