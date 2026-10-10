<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat sebuah aksi melanggar aturan bisnis (mis. sudah ada kegiatan lain yang
 * berlangsung, atau presensi diubah setelah kegiatan selesai). Pesannya aman ditampilkan ke kader.
 */
class AksiDitolakException extends RuntimeException
{
}
