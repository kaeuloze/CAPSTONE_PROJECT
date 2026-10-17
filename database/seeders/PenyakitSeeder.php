<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenyakitSeeder extends Seeder
{
    /**
     * Master Penyakit Tidak Menular (PTM), sesuai wireframe Tambah/Edit Warga.
     */
    public function run(): void
    {
        $daftar = ['Hipertensi', 'DM', 'Stroke', 'Penyakit Jantung', 'Asma', 'Kanker', 'Kolesterol Tinggi'];

        foreach ($daftar as $nama) {
            DB::table('penyakit')->updateOrInsert(
                ['nama_penyakit' => $nama],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
