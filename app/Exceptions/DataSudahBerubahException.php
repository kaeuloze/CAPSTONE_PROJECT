<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat kader menyimpan data yang sudah lebih dulu diubah kader lain
 * sejak formulirnya dibuka (pengaman pemakaian bersamaan antar-meja).
 */
class DataSudahBerubahException extends RuntimeException
{
    public function __construct(string $message = 'Data ini baru saja diubah oleh kader lain. Muat ulang halaman untuk melihat data terbaru, lalu ulangi perubahan Anda.')
    {
        parent::__construct($message);
    }
}
