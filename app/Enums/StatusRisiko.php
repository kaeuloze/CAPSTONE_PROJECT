<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status normal/berisiko (PENGUKURAN_DASAR.status_lingkar_perut, SKRINING_TBC.status_gejala_tbc).
 */
enum StatusRisiko: string
{
    use HasOptions;

    case Normal = 'normal';
    case Berisiko = 'berisiko';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Berisiko => 'Berisiko',
        };
    }
}
