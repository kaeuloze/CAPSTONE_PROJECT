<?php

namespace App\Models;

use App\Enums\KategoriRekap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitas REKAPITULASI: ringkasan laporan per kategori & periode (format Kemenkes).
 */
class Rekapitulasi extends Model
{
    protected $table = 'rekapitulasi';

    protected $primaryKey = 'id_rekapitulasi';

    protected $fillable = ['kategori_rekap', 'periode_bulan', 'periode_tahun', 'tanggal_dibuat', 'catatan'];

    protected function casts(): array
    {
        return [
            'kategori_rekap' => KategoriRekap::class,
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'tanggal_dibuat' => 'date',
        ];
    }

    /** Baris-baris rekap, berurutan sesuai format Kemenkes. */
    public function detail(): HasMany
    {
        return $this->hasMany(RekapitulasiDetail::class, 'id_rekapitulasi', 'id_rekapitulasi')->orderBy('urutan');
    }
}
