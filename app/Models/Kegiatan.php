<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitas KEGIATAN: setiap pelaksanaan posyandu.
 */
class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $primaryKey = 'id_kegiatan';

    protected $fillable = ['nama_kegiatan', 'tanggal_kegiatan', 'status_kegiatan', 'waktu_dibuka'];

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date',
            'status_kegiatan' => 'boolean', // true = Berlangsung, false = Selesai
            'waktu_dibuka' => 'datetime',
        ];
    }

    /** Hanya kegiatan yang sedang berlangsung (tab "Kegiatan Berlangsung"). */
    public function scopeBerlangsung(Builder $query): void
    {
        $query->where('status_kegiatan', true);
    }

    /** Hanya kegiatan yang sudah selesai (tab "Arsip Kegiatan"). */
    public function scopeSelesai(Builder $query): void
    {
        $query->where('status_kegiatan', false);
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function vaksinasi(): HasMany
    {
        return $this->hasMany(Vaksinasi::class, 'id_kegiatan', 'id_kegiatan');
    }
}
