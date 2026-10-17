<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaksinSeeder extends Seeder
{
    /**
     * Master vaksin imunisasi dasar, dicatat per dosis
     * (sesuai wireframe Gambar 2.60 "Imunisasi Dasar (per dosis)" & Gambar 2.61 Master Vaksin).
     */
    public function run(): void
    {
        $daftar = [
            'Hepatitis B (HB-0)' => 'Diberikan segera setelah lahir (<24 jam)',
            'BCG' => 'Pencegahan TBC berat, dosis tunggal',
            'Polio 1' => 'Polio dosis ke-1',
            'Polio 2' => 'Polio dosis ke-2',
            'Polio 3' => 'Polio dosis ke-3',
            'Polio 4' => 'Polio dosis ke-4',
            'DPT-HB-Hib 1' => 'Difteri, Pertusis, Tetanus, Hep B, Hib dosis ke-1',
            'DPT-HB-Hib 2' => 'Difteri, Pertusis, Tetanus, Hep B, Hib dosis ke-2',
            'DPT-HB-Hib 3' => 'Difteri, Pertusis, Tetanus, Hep B, Hib dosis ke-3',
            'Campak / MR' => 'Campak & Rubella',
            'IPV' => 'Polio suntik (Inactivated)',
        ];

        foreach ($daftar as $nama => $deskripsi) {
            DB::table('vaksin')->updateOrInsert(
                ['nama_vaksin' => $nama],
                ['deskripsi' => $deskripsi, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
