<?php

namespace App\Models;

use App\Enums\StatusRisiko;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas SKRINING_TBC: skrining gejala TBC.
 */
class SkriningTbc extends Model
{
    use MilikPemeriksaan;

    protected $table = 'skrining_tbc';

    protected $primaryKey = 'id_skrining_tbc';

    protected $fillable = [
        'id_pemeriksaan', 'batuk_terus_menerus', 'demam_2_minggu', 'bb_tidak_stabil',
        'kontak_pasien_tbc', 'status_gejala_tbc',
    ];

    protected function casts(): array
    {
        return [
            'batuk_terus_menerus' => 'boolean',
            'demam_2_minggu' => 'boolean',
            'bb_tidak_stabil' => 'boolean',
            'kontak_pasien_tbc' => 'boolean',
            'status_gejala_tbc' => StatusRisiko::class,
        ];
    }
}
