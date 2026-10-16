<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitas KEHADIRAN: presensi warga pada kegiatan.
 */
class Kehadiran extends Model
{
    protected $table = 'kehadiran';

    protected $primaryKey = 'id_kehadiran';

    protected $fillable = ['nik', 'id_kegiatan', 'status_kehadiran', 'waktu_kehadiran'];

    protected function casts(): array
    {
        return [
            'status_kehadiran' => 'boolean', // true = Hadir
            'waktu_kehadiran' => 'datetime',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'nik', 'nik');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }
}
