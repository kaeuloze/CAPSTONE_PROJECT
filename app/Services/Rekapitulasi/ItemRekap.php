<?php

namespace App\Services\Rekapitulasi;

/** Satu baris rekapitulasi (REKAPITULASI_DETAIL: kode_item, nama_item, nilai). */
final class ItemRekap
{
    public function __construct(
        public readonly string $kode,
        public readonly string $nama,
        public readonly int $nilai,
    ) {
    }
}
