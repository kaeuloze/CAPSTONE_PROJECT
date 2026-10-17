<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitas VAKSINASI: catatan vaksin (per dosis) yang diterima warga.
 */
class Vaksinasi extends Model
{
    protected $table = 'vaksinasi';

    protected $primaryKey = 'id_vaksinasi';

    protected $fillable = ['nik', 'id_vaksin', 'id_kegiatan', 'status_vaksinasi', 'tanggal_vaksinasi'];

    protected function casts(): array
    {
        return [
            'status_vaksinasi' => 'boolean', // true = Sudah
            'tanggal_vaksinasi' => 'date',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'nik', 'nik');
    }

    public function vaksin(): BelongsTo
    {
        return $this->belongsTo(Vaksin::class, 'id_vaksin', 'id_vaksin');
    }

    /** Boleh kosong untuk vaksin lama yang disalin dari buku KIA. */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }
}
