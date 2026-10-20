<?php

namespace App\Services;

use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Kader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Manajemen Pengguna / akun kader (SKF-11; Activity Diagram 2.10–2.13; Figma 141:164 & 141:2478).
 *
 * Aturan tambahan (disetujui 21 Okt 2026):
 * - Akun terakhir tidak bisa dihapus agar sistem selalu bisa dimasuki.
 * - Bila akun dihapus atau kata sandinya diganti, sesi login akun itu di perangkat lain otomatis keluar.
 * - Edit memakai penanda versi (pengaman edit bersamaan antar-kader, keputusan 26).
 */
class PenggunaService
{
    public function daftar(?string $cari): LengthAwarePaginator
    {
        $kata = trim((string) $cari);
        $pola = '%'.addcslashes($kata, '\\%_').'%';

        return Kader::query()
            ->select(['id_kader', 'nama_kader', 'username', 'created_at', 'updated_at'])
            ->when($kata !== '', fn ($q) => $q->where(fn ($q) => $q->where('nama_kader', 'like', $pola)->orWhere('username', 'like', $pola)))
            ->orderBy('nama_kader')
            ->orderBy('id_kader')
            ->paginate(config('posyandu.pengguna.per_halaman'))
            ->withQueryString();
    }

    /** @param array{nama_kader: string, username: string} $data */
    public function tambah(array $data, string $sandi): Kader
    {
        return Kader::create($data + ['password_hash' => $sandi]); // di-hash otomatis oleh model
    }

    /**
     * @param  array{nama_kader: string, username: string}  $data
     * @param  string|null  $sandiBaru  null = kata sandi tidak diubah
     * @param  string|null  $sesiPengubah  id sesi kader yang sedang menyimpan (tetap login bila mengubah akunnya sendiri)
     *
     * @throws DataSudahBerubahException bila akun baru saja diubah/dihapus kader lain
     */
    public function ubah(Kader $kader, array $data, ?string $sandiBaru, string $versi, ?string $sesiPengubah = null): Kader
    {
        return DB::transaction(function () use ($kader, $data, $sandiBaru, $versi, $sesiPengubah) {
            $terkini = Kader::query()->whereKey($kader->getKey())->lockForUpdate()->first();
            if ($terkini === null || $terkini->updated_at?->format('Y-m-d H:i:s') !== $versi) {
                throw new DataSudahBerubahException('Akun ini baru saja diubah atau dihapus oleh kader lain. Muat ulang halaman lalu ulangi perubahan Anda.');
            }

            $terkini->fill($data);
            if ($sandiBaru !== null) {
                $terkini->password_hash = $sandiBaru;
            }
            // Versi selalu berganti walau isinya sama, agar formulir lain yang masih terbuka ikut ditolak.
            $terkini->updated_at = $terkini->freshTimestamp();
            $terkini->save();

            if ($sandiBaru !== null) {
                $this->keluarkanSesi($terkini->getKey(), $sesiPengubah);
            }

            return $terkini;
        });
    }

    /** @throws AksiDitolakException bila akun ini satu-satunya akun yang tersisa */
    public function hapus(Kader $kader): void
    {
        DB::transaction(function () use ($kader) {
            // Kunci semua baris agar dua kader tidak bisa menghapus dua akun terakhir bersamaan.
            $semuaId = Kader::query()->lockForUpdate()->pluck('id_kader');
            if (! $semuaId->contains($kader->getKey())) {
                return; // sudah dihapus kader lain
            }
            if ($semuaId->count() <= 1) {
                throw new AksiDitolakException('Akun ini tidak bisa dihapus karena merupakan satu-satunya akun. Tambahkan akun lain terlebih dahulu.');
            }

            Kader::query()->whereKey($kader->getKey())->delete();
            $this->keluarkanSesi($kader->getKey());
        });
    }

    /**
     * Mengeluarkan sesi login sebuah akun di semua perangkat (kecuali sesi yang disebut).
     * Sesi disimpan di tabel `sessions` (SESSION_DRIVER=database), kolom user_id = id_kader.
     */
    private function keluarkanSesi(int $idKader, ?string $kecualiSesi = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $idKader)
            ->when($kecualiSesi !== null, fn ($q) => $q->where('id', '!=', $kecualiSesi))
            ->delete();
    }
}
