<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal aplikasi.
     * Data master (kader, penyakit, vaksin) selalu dijalankan;
     * data dummy (250 warga + riwayat 6 bulan) hanya dijalankan di lingkungan pengembangan (APP_ENV=local).
     */
    public function run(): void
    {
        $this->call([
            KaderSeeder::class,
            PenyakitSeeder::class,
            VaksinSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DataDummySeeder::class);
        }
    }
}
