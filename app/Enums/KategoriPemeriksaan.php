<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kategori form saat pemeriksaan (PEMERIKSAAN.kategori_usia_saat_periksa).
 */
enum KategoriPemeriksaan: string
{
    use HasOptions;

    case Remaja = 'remaja';
    case Dewasa = 'dewasa';
    case Lansia = 'lansia';
    case IbuHamil = 'ibu_hamil';
    case IbuNifas = 'ibu_nifas';

    public function label(): string
    {
        return match ($this) {
            self::Remaja => 'Remaja',
            self::Dewasa => 'Dewasa',
            self::Lansia => 'Lansia',
            self::IbuHamil => 'Ibu Hamil',
            self::IbuNifas => 'Ibu Nifas',
        };
    }
}
