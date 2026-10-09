<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status IMT (PENGUKURAN_DASAR.status_imt, PEMERIKSAAN_BUMIL.status_ploting_imt).
 */
enum StatusImt: string
{
    use HasOptions;

    case Kurus = 'kurus';
    case Normal = 'normal';
    case Gemuk = 'gemuk';
    case Obesitas = 'obesitas';

    public function label(): string
    {
        return match ($this) {
            self::Kurus => 'Kurus',
            self::Normal => 'Normal',
            self::Gemuk => 'Gemuk',
            self::Obesitas => 'Obesitas',
        };
    }
}
