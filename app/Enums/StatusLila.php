<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status ploting LILA ibu hamil (PEMERIKSAAN_BUMIL.status_ploting_lila).
 */
enum StatusLila: string
{
    use HasOptions;

    case Normal = 'normal';
    case Kek = 'kek';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Kek => 'KEK (Kurang Energi Kronis)',
        };
    }
}
