<?php

namespace App\Enums\Concerns;

/**
 * Fungsi bantu untuk semua Enum aplikasi.
 * Setiap Enum yang memakai trait ini wajib punya method label().
 */
trait HasOptions
{
    /** Daftar nilai mentah, mis. ['normal', 'hamil', ...] (untuk aturan validasi). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Pasangan nilai => label, mis. ['hamil' => 'Ibu Hamil'] (untuk pilihan dropdown di tampilan). */
    public static function options(): array
    {
        $hasil = [];
        foreach (self::cases() as $case) {
            $hasil[$case->value] = $case->label();
        }

        return $hasil;
    }
}
