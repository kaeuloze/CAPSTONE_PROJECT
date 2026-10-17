<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status kondisi khusus warga (WARGA.status_kondisi), ditandai manual oleh kader.
 */
enum StatusKondisi: string
{
    use HasOptions;

    case Normal = 'normal';
    case Hamil = 'hamil';
    case Nifas = 'nifas';
    case Menyusui = 'menyusui';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Tidak ada kondisi khusus',
            self::Hamil => 'Ibu Hamil',
            self::Nifas => 'Ibu Nifas',
            self::Menyusui => 'Ibu Menyusui',
        };
    }
}
