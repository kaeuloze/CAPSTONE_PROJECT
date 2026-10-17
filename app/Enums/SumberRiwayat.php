<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Asal data riwayat PTM (RIWAYAT_PTM.sumber_riwayat).
 */
enum SumberRiwayat: string
{
    use HasOptions;

    case KartuBantu = 'kartu_bantu';
    case Wawancara = 'wawancara';
    case FasilitasKesehatan = 'fasilitas_kesehatan';

    public function label(): string
    {
        return match ($this) {
            self::KartuBantu => 'Kartu Bantu',
            self::Wawancara => 'Wawancara',
            self::FasilitasKesehatan => 'Fasilitas Kesehatan',
        };
    }
}
