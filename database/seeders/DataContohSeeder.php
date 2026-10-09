<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataContohSeeder extends Seeder
{
    /**
     * Data contoh untuk pengujian di laptop pengembang (bukan data warga asli).
     * Tanggal lahir dihitung mundur dari hari ini agar kategori usia tetap sesuai.
     */
    public function run(): void
    {
        $warga = [
            // nik, nama, jenis_kelamin (1 = L, 0 = P), umur (tahun), status_kondisi, kategori_usia
            ['3404000000000001', 'Contoh Balita', 1, 2, 'normal', 'balita'],
            ['3404000000000002', 'Contoh Remaja Putri', 0, 15, 'normal', 'remaja'],
            ['3404000000000003', 'Contoh Remaja Putra', 1, 14, 'normal', 'remaja'],
            ['3404000000000004', 'Contoh Dewasa', 1, 35, 'normal', 'dewasa'],
            ['3404000000000005', 'Contoh Dewasa 45 Tahun', 0, 45, 'normal', 'dewasa'],
            ['3404000000000006', 'Contoh Lansia', 0, 67, 'normal', 'lansia'],
            ['3404000000000007', 'Contoh Ibu Hamil', 0, 28, 'hamil', 'dewasa'],
            ['3404000000000008', 'Contoh Ibu Nifas', 0, 25, 'nifas', 'dewasa'],
        ];

        foreach ($warga as [$nik, $nama, $jk, $umur, $kondisi, $kategori]) {
            DB::table('warga')->updateOrInsert(
                ['nik' => $nik],
                [
                    'nama_warga' => $nama,
                    'jenis_kelamin' => $jk,
                    'tanggal_lahir' => now()->subYears($umur)->subMonths(2)->toDateString(),
                    'no_hp' => null,
                    'alamat' => 'Tegalyoso, Banyuraden, Gamping, Sleman',
                    'status_kondisi' => $kondisi,
                    'kategori_usia' => $kategori,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('kegiatan')->updateOrInsert(
            ['nama_kegiatan' => 'Posyandu Contoh'],
            [
                'tanggal_kegiatan' => now()->toDateString(),
                'status_kegiatan' => true,
                'waktu_dibuka' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
