<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat data yang akan dihapus masih dipakai data lain
 * (keputusan 17: warga/kegiatan/vaksin/penyakit tidak bisa dihapus selama masih dipakai).
 */
class DataMasihDipakaiException extends RuntimeException
{
}
