<?php

namespace App\Models;

use App\Enums\StatusSensorik;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas SKRINING_SENSORIK: tes penglihatan & pendengaran (tab 6 bulan).
 */
class SkriningSensorik extends Model
{
    use MilikPemeriksaan;

    protected $table = 'skrining_sensorik';

    protected $primaryKey = 'id_skrining_sensorik';

    protected $fillable = [
        'id_pemeriksaan', 'mata_kanan', 'mata_kiri', 'telinga_kanan', 'telinga_kiri',
        'status_penglihatan', 'status_pendengaran',
    ];

    protected function casts(): array
    {
        return [
            'status_penglihatan' => StatusSensorik::class,
            'status_pendengaran' => StatusSensorik::class,
        ];
    }
}
