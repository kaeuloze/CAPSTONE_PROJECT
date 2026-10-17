<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status ASI eksklusif per bulan (ASI_EKSKLUSIF.status_asi).
 */
enum StatusAsi: string
{
    use HasOptions;

    case Ya = 'ya';
    case Tidak = 'tidak';
    case TidakRecall = 'tidak_recall';

    public function label(): string
    {
        return match ($this) {
            self::Ya => 'Ya',
            self::Tidak => 'Tidak',
            self::TidakRecall => 'Tidak Recall',
        };
    }
}
