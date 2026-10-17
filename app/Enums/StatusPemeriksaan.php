<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Status pemeriksaan: draft (Simpan) atau selesai (Submit) (PEMERIKSAAN.status_pemeriksaan).
 */
enum StatusPemeriksaan: string
{
    use HasOptions;

    case Draft = 'draft';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Belum Selesai',
            self::Selesai => 'Selesai',
        };
    }
}
