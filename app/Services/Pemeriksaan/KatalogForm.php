<?php

namespace App\Services\Pemeriksaan;

use App\Enums\KategoriPemeriksaan;

/**
 * Daftar formulir pemeriksaan yang sudah tersedia. Form ditambahkan satu per satu
 * (keputusan 39) di AppServiceProvider; kategori yang belum punya form menampilkan
 * halaman "Form sedang disiapkan".
 */
class KatalogForm
{
    /** @var array<string, DefinisiForm> */
    private array $form = [];

    public function daftarkan(DefinisiForm $definisi): void
    {
        $this->form[$definisi->kategori()->value] = $definisi;
    }

    public function untuk(KategoriPemeriksaan $kategori): ?DefinisiForm
    {
        return $this->form[$kategori->value] ?? null;
    }
}
