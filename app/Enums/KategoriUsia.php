<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kategori usia warga (WARGA.kategori_usia), ditentukan otomatis dari tanggal lahir.
 */
enum KategoriUsia: string
{
    use HasOptions;

    case Balita = 'balita';
    case Remaja = 'remaja';
    case Dewasa = 'dewasa';
    case Lansia = 'lansia';

    public function label(): string
    {
        return match ($this) {
            self::Balita => 'Balita',
            self::Remaja => 'Remaja',
            self::Dewasa => 'Dewasa',
            self::Lansia => 'Lansia',
        };
    }
}
