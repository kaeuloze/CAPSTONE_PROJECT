<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status rujukan (PENYULUHAN_RUJUKAN.status_rujukan).
 */
enum StatusRujukan: string
{
    use HasOptions;

    case TidakDirujuk = 'tidak_dirujuk';
    case RujukPuskesmas = 'rujuk_puskesmas';

    public function label(): string
    {
        return match ($this) {
            self::TidakDirujuk => 'Tidak dirujuk',
            self::RujukPuskesmas => 'Rujuk Puskesmas',
        };
    }
}
