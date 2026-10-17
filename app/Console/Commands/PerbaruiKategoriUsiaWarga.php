<?php

namespace App\Console\Commands;

use App\Enums\KategoriUsia;
use App\Models\Warga;
use Illuminate\Console\Command;

/**
 * Mutasi usia otomatis (SKF-05): memperbarui kolom warga.kategori_usia untuk warga
 * yang melewati batas usia (mis. Balita -> Remaja saat berusia 5 tahun).
 * Dijalankan otomatis setiap hari (routes/console.php) dan bisa dijalankan manual.
 */
class PerbaruiKategoriUsiaWarga extends Command
{
    protected $signature = 'warga:perbarui-kategori';

    protected $description = 'Memperbarui kategori usia warga sesuai tanggal lahir per hari ini';

    public function handle(): int
    {
        $total = 0;

        foreach (KategoriUsia::cases() as $kategori) {
            // toBase(): updated_at tidak diubah karena data identitas warga tidak berubah.
            $total += Warga::query()
                ->kategori($kategori)
                ->where('kategori_usia', '!=', $kategori->value)
                ->toBase()
                ->update(['kategori_usia' => $kategori->value]);
        }

        $this->info("Kategori usia diperbarui untuk {$total} warga.");

        return self::SUCCESS;
    }
}
