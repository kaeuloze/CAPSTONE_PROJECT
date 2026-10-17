<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KaderSeeder extends Seeder
{
    /**
     * Membuat satu akun kader awal dari konfigurasi .env (KADER_AWAL_*).
     * Password disimpan dalam bentuk hash, tidak pernah plaintext.
     */
    public function run(): void
    {
        $akun = config('posyandu.kader_awal');

        if (blank($akun['username']) || blank($akun['password'])) {
            $this->command?->warn('KADER_AWAL_USERNAME / KADER_AWAL_PASSWORD di .env belum diisi. Akun kader awal tidak dibuat.');

            return;
        }

        DB::table('kader')->updateOrInsert(
            ['username' => $akun['username']],
            [
                'password_hash' => Hash::make($akun['password']),
                'nama_kader' => $akun['nama'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
