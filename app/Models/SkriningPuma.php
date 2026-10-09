<?php

namespace App\Models;

use App\Enums\StatusPpok;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas SKRINING_PUMA: skrining PPOK (usia > 40 tahun, tab tahunan).
 */
class SkriningPuma extends Model
{
    use MilikPemeriksaan;

    protected $table = 'skrining_puma';

    protected $primaryKey = 'id_puma';

    protected $fillable = [
        'id_pemeriksaan', 'skor_jenis_kelamin', 'skor_usia', 'skor_merokok', 'gejala_napas_pendek',
        'gejala_dahak', 'gejala_batuk_tanpa_flu', 'pernah_spirometri', 'skor_puma_total', 'status_ppok',
    ];

    protected function casts(): array
    {
        return [
            'skor_jenis_kelamin' => 'integer',
            'skor_usia' => 'integer',
            'skor_merokok' => 'integer',
            'gejala_napas_pendek' => 'boolean',
            'gejala_dahak' => 'boolean',
            'gejala_batuk_tanpa_flu' => 'boolean',
            'pernah_spirometri' => 'boolean',
            'skor_puma_total' => 'integer',
            'status_ppok' => StatusPpok::class,
        ];
    }
}
