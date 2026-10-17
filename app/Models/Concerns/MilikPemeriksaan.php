<?php

namespace App\Models\Concerns;

use App\Models\Pemeriksaan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relasi bersama untuk semua tabel detail pemeriksaan
 * (pengukuran_dasar, skrining_*, pemeriksaan_bumil, penyuluhan_rujukan).
 */
trait MilikPemeriksaan
{
    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }
}
