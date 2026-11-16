<?php

namespace App\Services;

/**
 * Alamat file JS/CSS di folder public dengan tanda versi, misalnya js/presensi.js?v=1a2b3c4d.
 *
 * Hosting menyimpan file JS/CSS di cache browser selama 7 hari. Tanpa tanda versi, HP yang pernah
 * membuka halaman tetap memakai file lama setelah website diperbarui. Tanda versi diambil dari isi
 * file, jadi alamatnya hanya berubah bila isi file berubah.
 */
class AsetPublik
{
    /** @var array<string, string> Tanda versi yang sudah dihitung dalam satu permintaan. */
    private static array $versi = [];

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        if (! array_key_exists($path, self::$versi)) {
            $file = public_path($path);
            self::$versi[$path] = is_file($file) ? substr(md5_file($file), 0, 8) : '';
        }

        $versi = self::$versi[$path];

        return asset($path).($versi !== '' ? '?v='.$versi : '');
    }
}
