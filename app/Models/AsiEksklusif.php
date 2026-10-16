<?php

namespace App\Models;

use App\Enums\StatusAsi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entitas ASI_EKSKLUSIF: pemantauan ASI eksklusif bulan ke-1 s.d. 6.
 */
class AsiEksklusif extends Model
{
    protected $table = 'asi_eksklusif';

    protected $primaryKey = 'id_asi';

    protected $fillable = ['nik', 'bulan_ke', 'status_asi', 'tanggal_dicatat'];

    protected function casts(): array
    {
        return [
            'bulan_ke' => 'integer',
            'status_asi' => StatusAsi::class,
            'tanggal_dicatat' => 'date',
        ];
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'nik', 'nik');
    }
}
