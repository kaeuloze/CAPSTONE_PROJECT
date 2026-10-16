<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Jenis pemeriksaan dalam satu kunjungan (PEMERIKSAAN.jenis_pemeriksaan, tipe SET).
 */
enum JenisPemeriksaan: string
{
    use HasOptions;

    case Rutin = 'rutin';
    case EnamBulanan = 'enam_bulanan';
    case Tahunan = 'tahunan';

    public function label(): string
    {
        return match ($this) {
            self::Rutin => 'Rutin Bulanan',
            self::EnamBulanan => '6 Bulan Sekali',
            self::Tahunan => 'Tahunan',
        };
    }
}
