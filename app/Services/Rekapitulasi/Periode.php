<?php

namespace App\Services\Rekapitulasi;

use Carbon\CarbonImmutable;

/** Periode rekapitulasi = satu bulan kalender (filter Bulan/Tahun di Activity Diagram 2.33–2.35). */
final class Periode
{
    public function __construct(public readonly int $bulan, public readonly int $tahun)
    {
    }

    public static function dariTanggal(\DateTimeInterface $tanggal): self
    {
        return new self((int) $tanggal->format('n'), (int) $tanggal->format('Y'));
    }

    public function awal(): CarbonImmutable
    {
        return CarbonImmutable::create($this->tahun, $this->bulan, 1)->startOfDay();
    }

    public function akhir(): CarbonImmutable
    {
        return $this->awal()->endOfMonth()->startOfDay();
    }

    /** Mis. "Mei 2026". */
    public function label(): string
    {
        return $this->awal()->translatedFormat('F Y');
    }

    /** Untuk nama file, mis. "2026-05". */
    public function kode(): string
    {
        return sprintf('%04d-%02d', $this->tahun, $this->bulan);
    }

    public function setelahBulanIni(): bool
    {
        return $this->awal()->greaterThan(CarbonImmutable::now()->startOfMonth());
    }
}
