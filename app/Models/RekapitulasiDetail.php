<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitas REKAPITULASI_DETAIL: satu baris item pada rekapitulasi.
 */
class RekapitulasiDetail extends Model
{
    protected $table = 'rekapitulasi_detail';

    protected $primaryKey = 'id_rekap_detail';

    protected $fillable = ['id_rekapitulasi', 'kode_item', 'nama_item', 'nilai', 'urutan'];

    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function rekapitulasi(): BelongsTo
    {
        return $this->belongsTo(Rekapitulasi::class, 'id_rekapitulasi', 'id_rekapitulasi');
    }
}
