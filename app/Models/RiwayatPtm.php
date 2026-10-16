<?php

namespace App\Models;

use App\Enums\SumberRiwayat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitas RIWAYAT_PTM: riwayat penyakit tidak menular warga.
 */
class RiwayatPtm extends Model
{
    protected $table = 'riwayat_ptm';

    protected $primaryKey = 'id_riwayat';

    protected $fillable = ['nik', 'id_penyakit', 'sumber_riwayat', 'tanggal_dicatat'];

    protected function casts(): array
    {
        return [
            'sumber_riwayat' => SumberRiwayat::class,
            'tanggal_dicatat' => 'date',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'nik', 'nik');
    }

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class, 'id_penyakit', 'id_penyakit');
    }
}
