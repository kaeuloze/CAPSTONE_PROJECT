<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Kategori usia warga (WARGA.kategori_usia), ditentukan otomatis dari tanggal lahir.
 *
 * Batas usia diatur di config/posyandu.php ('kategori_usia'):
 * Balita < 5 tahun, Remaja 5–17, Dewasa 18–59, Lansia >= 60.
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

    /** Usia minimal (tahun) untuk masuk kategori ini. */
    public function usiaMulai(): int
    {
        return match ($this) {
            self::Balita => 0,
            self::Remaja => (int) config('posyandu.kategori_usia.remaja_mulai'),
            self::Dewasa => (int) config('posyandu.kategori_usia.dewasa_mulai'),
            self::Lansia => (int) config('posyandu.kategori_usia.lansia_mulai'),
        };
    }

    /** Kategori berikutnya (untuk batas atas usia), null jika Lansia. */
    public function berikutnya(): ?self
    {
        return match ($this) {
            self::Balita => self::Remaja,
            self::Remaja => self::Dewasa,
            self::Dewasa => self::Lansia,
            self::Lansia => null,
        };
    }

    /**
     * Menentukan kategori dari tanggal lahir pada tanggal acuan (default: hari ini).
     * Memakai aturan yang sama dengan rentangTanggalLahir() agar badge dan filter selalu cocok.
     */
    public static function dariTanggalLahir(CarbonInterface|string $tanggalLahir, ?CarbonInterface $acuan = null): self
    {
        $lahir = CarbonImmutable::parse($tanggalLahir)->startOfDay();

        foreach (array_reverse(self::cases()) as $kategori) {
            if ($lahir->lessThanOrEqualTo(self::batasLahir($kategori->usiaMulai(), $acuan))) {
                return $kategori;
            }
        }

        return self::Balita;
    }

    /**
     * Rentang tanggal lahir untuk kategori ini pada tanggal acuan, dipakai untuk filter di database.
     *
     * @return array{sampai: string, setelah: ?string} tanggal_lahir <= sampai DAN tanggal_lahir > setelah
     */
    public function rentangTanggalLahir(?CarbonInterface $acuan = null): array
    {
        $berikutnya = $this->berikutnya();

        return [
            'sampai' => self::batasLahir($this->usiaMulai(), $acuan)->toDateString(),
            'setelah' => $berikutnya ? self::batasLahir($berikutnya->usiaMulai(), $acuan)->toDateString() : null,
        ];
    }

    /** Tanggal lahir paling akhir agar seseorang sudah berusia $tahun pada tanggal acuan. */
    private static function batasLahir(int $tahun, ?CarbonInterface $acuan): CarbonImmutable
    {
        $hariIni = CarbonImmutable::parse($acuan ?? now())->startOfDay();

        // NoOverflow: hasil tidak meloncat ke bulan berikutnya. Warga lahir 29 Februari
        // dihitung bertambah usia pada 1 Maret di tahun bukan kabisat.
        return $hariIni->subYearsNoOverflow($tahun);
    }
}
