<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Jenis pemeriksaan ibu (PEMERIKSAAN_BUMIL.jenis_ibu).
 */
enum JenisIbu: string
{
    use HasOptions;

    case Hamil = 'hamil';
    case Nifas = 'nifas';

    public function label(): string
    {
        return match ($this) {
            self::Hamil => 'Ibu Hamil',
            self::Nifas => 'Ibu Nifas',
        };
    }
}
