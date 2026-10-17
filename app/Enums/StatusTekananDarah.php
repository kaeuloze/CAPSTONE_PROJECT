<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status tekanan darah (PENGUKURAN_DASAR.status_tekanan_darah, PEMERIKSAAN_BUMIL.status_ploting_tekanan_darah).
 */
enum StatusTekananDarah: string
{
    use HasOptions;

    case Rendah = 'rendah';
    case Normal = 'normal';
    case Tinggi = 'tinggi';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Normal => 'Normal',
            self::Tinggi => 'Tinggi',
        };
    }
}
