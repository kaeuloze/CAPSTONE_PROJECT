<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Tingkat ketergantungan hasil skor AKS (SKRINING_AKS.status_aks).
 */
enum StatusAks: string
{
    use HasOptions;

    case Mandiri = 'mandiri';
    case Ringan = 'ringan';
    case Sedang = 'sedang';
    case Berat = 'berat';
    case Total = 'total';

    public function label(): string
    {
        return match ($this) {
            self::Mandiri => 'Mandiri',
            self::Ringan => 'Ketergantungan Ringan',
            self::Sedang => 'Ketergantungan Sedang',
            self::Berat => 'Ketergantungan Berat',
            self::Total => 'Ketergantungan Total',
        };
    }
}
