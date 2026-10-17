<?php

namespace App\Services\Pemeriksaan;

use App\Enums\KategoriUsia;
use App\Enums\StatusPemeriksaan;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis menu Riwayat Pemeriksaan (Figma 198:723, 141:921, 141:3062, 253:1732).
 *
 * - Daftar berisi SELURUH warga (disetujui), dengan tanggal kunjungan terakhir.
 * - Riwayat per warga hanya memuat pemeriksaan yang sudah di-Submit (draft tetap di menu Pemeriksaan).
 * - Edit memakai aturan yang sama dengan formulir: simpan per bagian (aman antar-meja), isian * tetap wajib,
 *   jenis pemeriksaan & hasil otomatis dihitung ulang.
 * - Hapus: pemeriksaan beserta detailnya terhapus; bila kegiatannya masih berlangsung, warga otomatis
 *   muncul lagi di antrean menu Pemeriksaan (disetujui) karena masih tercatat Hadir.
 */
class RiwayatService
{
    public function __construct(private readonly PemeriksaanService $pemeriksaan)
    {
    }

    /** Daftar Warga Riwayat Pemeriksaan: cari nama/NIK, filter kategori usia, kunjungan terakhir. */
    public function daftarWarga(?string $cari, ?KategoriUsia $kategori): LengthAwarePaginator
    {
        // Kunjungan terakhir = tanggal terbaru dari pemeriksaan yang di-Submit, kegiatan saat vaksin dicatat,
        // atau tanggal ASI dicatat (disetujui: vaksin & ASI ikut tercatat di Riwayat).
        $periksa = '(SELECT MAX(p.tanggal_pemeriksaan) FROM pemeriksaan p WHERE p.nik = warga.nik AND p.status_pemeriksaan = ?)';
        $vaksin = '(SELECT MAX(COALESCE(kg.tanggal_kegiatan, v.tanggal_vaksinasi)) FROM vaksinasi v LEFT JOIN kegiatan kg ON kg.id_kegiatan = v.id_kegiatan WHERE v.nik = warga.nik AND v.status_vaksinasi = 1)';
        $asi = '(SELECT MAX(a.tanggal_dicatat) FROM asi_eksklusif a WHERE a.nik = warga.nik)';
        $kunjunganTerakhir = "NULLIF(GREATEST(COALESCE({$periksa}, '1000-01-01'), COALESCE({$vaksin}, '1000-01-01'), COALESCE({$asi}, '1000-01-01')), '1000-01-01')";

        return Warga::query()
            ->cari($cari)
            ->when($kategori, fn ($q) => $q->kategori($kategori))
            ->select('warga.*')
            ->selectRaw("{$kunjunganTerakhir} as kunjungan_terakhir", [\App\Enums\StatusPemeriksaan::Selesai->value])
            ->orderBy('warga.nama_warga')
            ->orderBy('warga.nik')
            ->paginate(config('posyandu.riwayat.per_halaman'))
            ->withQueryString();
    }

    /** Pemeriksaan yang sudah di-Submit milik satu warga, terbaru di atas. */
    public function riwayatWarga(Warga $warga): LengthAwarePaginator
    {
        return Pemeriksaan::query()
            ->selesai()
            ->where('nik', $warga->nik)
            ->with(['kegiatan', 'pengukuranDasar', 'skriningTbc', 'skriningPuma', 'skriningAks', 'skriningSkilas', 'pemeriksaanBumil', 'penyuluhanRujukan'])
            ->orderByDesc('tanggal_pemeriksaan')
            ->orderByDesc('id_pemeriksaan')
            ->paginate(config('posyandu.riwayat.per_halaman'));
    }

    /**
     * Simpan Perubahan dari halaman Edit Pemeriksaan.
     *
     * @throws AksiDitolakException|DataSudahBerubahException|ValidationException
     */
    public function ubah(Pemeriksaan $pemeriksaan, DefinisiForm $form, array $isian, array $versi, ?array $diubah): Pemeriksaan
    {
        return DB::transaction(function () use ($pemeriksaan, $form, $isian, $versi, $diubah) {
            $terkunci = $this->kunci($pemeriksaan);
            $warga = $terkunci->warga;

            $this->pemeriksaan->terapkanIsian($terkunci, $terkunci->kegiatan, $warga, $form, $isian, $versi, $diubah);
            $this->pemeriksaan->tandaiSelesai($terkunci, $form, $warga); // cek isian * & hitung ulang jenis
            $terkunci->touch();

            return $terkunci->refresh();
        });
    }

    /** Menghapus pemeriksaan beserta seluruh detailnya (ON DELETE CASCADE, keputusan 17). */
    public function hapus(Pemeriksaan $pemeriksaan): void
    {
        DB::transaction(fn () => $this->kunci($pemeriksaan)->delete());
    }

    /** @throws AksiDitolakException bila pemeriksaan sudah dihapus kader lain */
    private function kunci(Pemeriksaan $pemeriksaan): Pemeriksaan
    {
        $terkunci = Pemeriksaan::query()->whereKey($pemeriksaan->getKey())->lockForUpdate()->first();
        if (! $terkunci || $terkunci->status_pemeriksaan !== StatusPemeriksaan::Selesai) {
            throw new AksiDitolakException('Pemeriksaan ini baru saja dihapus oleh kader lain.');
        }

        return $terkunci;
    }
}
