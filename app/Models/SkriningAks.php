<?php

namespace App\Models;

use App\Enums\StatusAks;
use App\Models\Concerns\MilikPemeriksaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Entitas SKRINING_AKS: Aktivitas Kehidupan Sehari-hari lansia (tab tahunan).
 */
class SkriningAks extends Model
{
    use MilikPemeriksaan;

    /** Sepuluh item skor AKS (urutan sesuai formulir). */
    public const ITEM_SKOR = [
        'skor_bab', 'skor_bak', 'skor_kebersihan_diri', 'skor_penggunaan_wc', 'skor_makan_minum',
        'skor_transfer_kursi', 'skor_berjalan', 'skor_berpakaian', 'skor_naik_tangga', 'skor_mandi',
    ];

    protected $table = 'skrining_aks';

    protected $primaryKey = 'id_aks';

    protected $fillable = [
        'id_pemeriksaan', ...self::ITEM_SKOR, 'skor_total', 'status_aks',
    ];

    protected function casts(): array
    {
        return array_fill_keys([...self::ITEM_SKOR, 'skor_total'], 'integer')
            + ['status_aks' => StatusAks::class];
    }
}
