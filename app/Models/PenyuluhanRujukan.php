<?php

namespace App\Models;

use App\Enums\StatusRujukan;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas PENYULUHAN_RUJUKAN: penyuluhan & rujukan pada setiap pemeriksaan.
 */
class PenyuluhanRujukan extends Model
{
    use MilikPemeriksaan;

    protected $table = 'penyuluhan_rujukan';

    protected $primaryKey = 'id_penyuluhan';

    protected $fillable = ['id_pemeriksaan', 'topik_penyuluhan', 'status_rujukan', 'tanggal_rujukan'];

    protected function casts(): array
    {
        return [
            'status_rujukan' => StatusRujukan::class,
            'tanggal_rujukan' => 'date',
        ];
    }
}
