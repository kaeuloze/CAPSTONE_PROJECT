<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Kategori rekapitulasi format Kemenkes (REKAPITULASI.kategori_rekap).
 */
enum KategoriRekap: string
{
    use HasOptions;

    case Remaja = 'remaja';
    case IbuHamilNifas = 'ibu_hamil_nifas';
    case DewasaLansia = 'dewasa_lansia';

    public function label(): string
    {
        return match ($this) {
            self::Remaja => 'Anak Usia Sekolah & Remaja',
            self::IbuHamilNifas => 'Ibu Hamil / Nifas',
            self::DewasaLansia => 'Dewasa dan Lansia',
        };
    }
}
