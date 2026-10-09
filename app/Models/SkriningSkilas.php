<?php

namespace App\Models;

use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas SKRINING_SKILAS: Skrining Lansia Sederhana (tab tahunan).
 */
class SkriningSkilas extends Model
{
    use MilikPemeriksaan;

    /** Dua belas pertanyaan ya/tidak (urutan sesuai ERD & formulir). */
    public const ITEM = [
        'orientasi_waktu_tempat', 'mengulang_tiga_kata', 'tes_berdiri_kursi', 'bb_berkurang',
        'hilang_nafsu_makan', 'lila_kurang', 'masalah_penglihatan', 'tes_melihat', 'tes_berbisik',
        'perasaan_sedih', 'kurang_minat', 'imunisasi_covid',
    ];

    protected $table = 'skrining_skilas';

    protected $primaryKey = 'id_skilas';

    protected $fillable = ['id_pemeriksaan', ...self::ITEM];

    protected function casts(): array
    {
        return array_fill_keys(self::ITEM, 'boolean');
    }
}
