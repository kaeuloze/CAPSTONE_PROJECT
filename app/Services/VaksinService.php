<?php

namespace App\Services;

use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Vaksin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Master Vaksin (Figma 192:320 & 208:894; Activity Diagram 2.21–2.24).
 * Setiap dosis adalah satu vaksin (keputusan 11), urutan checklist = urutan ditambahkan.
 */
class VaksinService
{
    public function daftar(?string $cari): LengthAwarePaginator
    {
        $kata = trim((string) $cari);

        return Vaksin::query()
            ->when($kata !== '', fn ($q) => $q->where('nama_vaksin', 'like', '%'.addcslashes($kata, '\\%_').'%'))
            ->withCount('vaksinasi')
            ->orderBy('id_vaksin')
            ->paginate(config('posyandu.vaksinasi.per_halaman'))
            ->withQueryString();
    }

    public function tambah(array $data): Vaksin
    {
        return Vaksin::create($data);
    }

    /** @throws DataSudahBerubahException bila vaksin baru saja diubah/dihapus kader lain */
    public function ubah(Vaksin $vaksin, array $data, string $versi): Vaksin
    {
        return DB::transaction(function () use ($vaksin, $data, $versi) {
            $terkini = Vaksin::query()->whereKey($vaksin->getKey())->lockForUpdate()->first();
            if ($terkini === null || $terkini->updated_at?->format('Y-m-d H:i:s') !== $versi) {
                throw new DataSudahBerubahException('Data vaksin ini baru saja diubah atau dihapus oleh kader lain. Muat ulang halaman lalu ulangi perubahan Anda.');
            }
            $terkini->update($data);

            return $terkini;
        });
    }

    /** @throws DataMasihDipakaiException bila vaksin sudah tercatat pada balita (keputusan 17: RESTRICT) */
    public function hapus(Vaksin $vaksin): void
    {
        DB::transaction(function () use ($vaksin) {
            $terkini = Vaksin::query()->whereKey($vaksin->getKey())->lockForUpdate()->first();
            if ($terkini === null) {
                return; // sudah dihapus kader lain
            }
            if ($terkini->vaksinasi()->exists()) {
                throw new DataMasihDipakaiException("Vaksin {$terkini->nama_vaksin} tidak bisa dihapus karena sudah tercatat pada data vaksinasi balita.");
            }
            $terkini->delete();
        });
    }
}
