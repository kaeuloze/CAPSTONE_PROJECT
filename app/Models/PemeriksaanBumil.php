<?php

namespace App\Models;

use App\Enums\JenisIbu;
use App\Enums\StatusImt;
use App\Enums\StatusLila;
use App\Enums\StatusTekananDarah;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas PEMERIKSAAN_BUMIL: pemeriksaan khusus ibu hamil / nifas.
 */
class PemeriksaanBumil extends Model
{
    use MilikPemeriksaan;

    protected $table = 'pemeriksaan_bumil';

    protected $primaryKey = 'id_bumil';

    protected $fillable = [
        'id_pemeriksaan', 'jenis_ibu', 'usia_kehamilan_minggu', 'status_ploting_imt', 'status_ploting_lila',
        'status_ploting_tekanan_darah', 'jumlah_ttd_mms', 'status_konsumsi_ttd_rutin', 'komposisi_mt_bumil_kek',
        'status_rutin_mt_kek', 'ikut_kelas_ibu_hamil', 'jumlah_kapsul_vit_a', 'status_rutin_vit_a',
        'status_menyusui', 'status_kb_pascasalin',
    ];

    protected function casts(): array
    {
        return [
            'jenis_ibu' => JenisIbu::class,
            'status_ploting_imt' => StatusImt::class,
            'status_ploting_lila' => StatusLila::class,
            'status_ploting_tekanan_darah' => StatusTekananDarah::class,
            'jumlah_ttd_mms' => 'integer',
            'status_konsumsi_ttd_rutin' => 'boolean',
            'status_rutin_mt_kek' => 'boolean',
            'ikut_kelas_ibu_hamil' => 'boolean',
            'jumlah_kapsul_vit_a' => 'integer',
            'status_rutin_vit_a' => 'boolean',
            'status_menyusui' => 'boolean',
            'status_kb_pascasalin' => 'boolean',
        ];
    }
}
