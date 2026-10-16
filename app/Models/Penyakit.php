<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitas PENYAKIT: master Penyakit Tidak Menular (PTM).
 */
class Penyakit extends Model
{
    protected $table = 'penyakit';

    protected $primaryKey = 'id_penyakit';

    protected $fillable = ['nama_penyakit'];

    public function riwayatPtm(): HasMany
    {
        return $this->hasMany(RiwayatPtm::class, 'id_penyakit', 'id_penyakit');
    }

    /** Warga yang memiliki riwayat penyakit ini. */
    public function warga(): BelongsToMany
    {
        return $this->belongsToMany(Warga::class, 'riwayat_ptm', 'id_penyakit', 'nik', 'id_penyakit', 'nik')
            ->withPivot(['id_riwayat', 'sumber_riwayat', 'tanggal_dicatat'])
            ->withTimestamps();
    }
}
