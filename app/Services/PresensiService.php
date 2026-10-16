<?php

namespace App\Services;

use App\Enums\KategoriUsia;
use App\Models\Kegiatan;
use App\Models\Warga;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Pengambilan data halaman Presensi (wireframe 2.42): daftar warga beserta status kehadirannya,
 * dan ringkasan Total / Hadir / Tidak Hadir yang ikut menyesuaikan pencarian & filter kategori.
 *
 * - Kegiatan berlangsung: semua warga tampil; yang belum punya catatan = "Belum".
 * - Kegiatan selesai (arsip): hanya warga yang tercatat pada kegiatan itu (Hadir / Tidak Hadir).
 * - Kategori usia dihitung per tanggal kegiatan.
 */
class PresensiService
{
    public function daftar(Kegiatan $kegiatan, ?string $cari, ?KategoriUsia $kategori): LengthAwarePaginator
    {
        return $this->dasar($kegiatan, $cari, $kategori)
            ->select(['warga.nik', 'warga.nama_warga', 'warga.tanggal_lahir', 'warga.jenis_kelamin', 'warga.status_kondisi'])
            ->selectRaw('COALESCE(k.status_kehadiran, 0) as hadir')
            ->orderBy('warga.nama_warga')
            ->orderBy('warga.nik')
            ->paginate(config('posyandu.presensi.per_halaman'))
            ->withQueryString();
    }

    /** @return array{total: int, hadir: int, tidak_hadir: int} */
    public function ringkasan(Kegiatan $kegiatan, ?string $cari, ?KategoriUsia $kategori): array
    {
        $hasil = $this->dasar($kegiatan, $cari, $kategori)
            ->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(COALESCE(k.status_kehadiran, 0)), 0) as hadir')
            ->first();

        $total = (int) $hasil->total;
        $hadir = (int) $hasil->hadir;

        return ['total' => $total, 'hadir' => $hadir, 'tidak_hadir' => $total - $hadir];
    }

    /** Label kelompok kehadiran di kartu Kegiatan Berlangsung (Figma "Daftar Kegiatan — hasil rev"). */
    public const KELOMPOK = [
        'balita' => 'Balita',
        'remaja' => 'Remaja',
        'dewasa' => 'Dewasa',
        'lansia' => 'Lansia',
        'ibu' => 'Ibu Hamil / Nifas',
    ];

    /**
     * Kehadiran per kelompok (Hadir / Total). Ibu hamil/nifas/menyusui dihitung terpisah di kelompok
     * "Ibu Hamil / Nifas" dan tidak ikut kategori usianya (disetujui), sehingga jumlah semua kelompok = Total.
     * Kategori usia dihitung per tanggal kegiatan.
     *
     * @return array<string, array{label: string, hadir: int, total: int}>
     */
    public function ringkasanPerKelompok(Kegiatan $kegiatan): array
    {
        $acuan = $kegiatan->tanggal_kegiatan;
        $sampai = fn (KategoriUsia $k) => $k->rentangTanggalLahir($acuan)['sampai'];

        $baris = $this->dasar($kegiatan, null, null)
            ->toBase()
            ->selectRaw(
                "CASE
                    WHEN warga.status_kondisi <> 'normal' THEN 'ibu'
                    WHEN warga.tanggal_lahir <= ? THEN 'lansia'
                    WHEN warga.tanggal_lahir <= ? THEN 'dewasa'
                    WHEN warga.tanggal_lahir <= ? THEN 'remaja'
                    ELSE 'balita'
                 END as kelompok, COUNT(*) as total, COALESCE(SUM(COALESCE(k.status_kehadiran, 0)), 0) as hadir",
                [$sampai(KategoriUsia::Lansia), $sampai(KategoriUsia::Dewasa), $sampai(KategoriUsia::Remaja)]
            )
            ->groupBy('kelompok')
            ->get()
            ->keyBy('kelompok');

        $hasil = [];
        foreach (self::KELOMPOK as $kunci => $label) {
            $hasil[$kunci] = [
                'label' => $label,
                'hadir' => (int) ($baris[$kunci]->hadir ?? 0),
                'total' => (int) ($baris[$kunci]->total ?? 0),
            ];
        }

        return $hasil;
    }

    private function dasar(Kegiatan $kegiatan, ?string $cari, ?KategoriUsia $kategori): Builder
    {
        $gabung = function (JoinClause $join) use ($kegiatan) {
            $join->on('k.nik', '=', 'warga.nik')->where('k.id_kegiatan', '=', $kegiatan->id_kegiatan);
        };

        $query = Warga::query();
        $kegiatan->status_kegiatan
            ? $query->leftJoin('kehadiran as k', $gabung)
            : $query->join('kehadiran as k', $gabung);

        return $query->cari($cari)->kategori($kategori, $kegiatan->tanggal_kegiatan);
    }
}
