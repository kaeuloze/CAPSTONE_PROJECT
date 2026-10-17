<?php

namespace App\Models;

use App\Enums\StatusImt;
use App\Enums\StatusRisiko;
use App\Enums\StatusTekananDarah;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas PENGUKURAN_DASAR: hasil penimbangan & pengukuran (tab Rutin).
 */
class PengukuranDasar extends Model
{
    use MilikPemeriksaan;

    protected $table = 'pengukuran_dasar';

    protected $primaryKey = 'id_pengukuran';

    protected $fillable = [
        'id_pemeriksaan', 'berat_badan', 'tinggi_badan', 'lingkar_perut', 'lila', 'tekanan_darah',
        'kadar_gula_darah', 'kadar_hb', 'status_imt', 'status_tekanan_darah', 'status_lingkar_perut',
    ];

    protected function casts(): array
    {
        return [
            'berat_badan' => 'decimal:2',
            'tinggi_badan' => 'decimal:2',
            'lingkar_perut' => 'decimal:2',
            'lila' => 'decimal:1',
            'kadar_gula_darah' => 'decimal:1',
            'kadar_hb' => 'decimal:1',
            'status_imt' => StatusImt::class,
            'status_tekanan_darah' => StatusTekananDarah::class,
            'status_lingkar_perut' => StatusRisiko::class,
        ];
    }
}
