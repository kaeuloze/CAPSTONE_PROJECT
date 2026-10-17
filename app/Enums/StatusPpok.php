<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status risiko PPOK hasil skor PUMA (SKRINING_PUMA.status_ppok).
 */
enum StatusPpok: string
{
    use HasOptions;

    case TidakBerisiko = 'tidak_berisiko';
    case Berisiko = 'berisiko';

    public function label(): string
    {
        return match ($this) {
            self::TidakBerisiko => 'Tidak Berisiko PPOK',
            self::Berisiko => 'Berisiko PPOK',
        };
    }
}
