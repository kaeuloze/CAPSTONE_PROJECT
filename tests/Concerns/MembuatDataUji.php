<?php

namespace Tests\Concerns;

use App\Enums\KategoriPemeriksaan;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Warga;

/**
 * Pembuat data uji bersama untuk semua pengujian.
 */
trait MembuatDataUji
{
    protected function buatKader(string $username = 'kader_uji', string $password = 'RahasiaUji123'): Kader
    {
        return Kader::create([
            'username' => $username,
            'password_hash' => $password, // otomatis di-hash oleh model
            'nama_kader' => 'Kader Uji',
        ]);
    }

    protected function buatWarga(string $nik = '3404000000000099', array $ubah = []): Warga
    {
        return Warga::create(array_merge([
            'nik' => $nik,
            'nama_warga' => 'Warga Uji',
            'jenis_kelamin' => false,
            'tanggal_lahir' => now()->subYears(30)->toDateString(),
            'alamat' => 'Tegalyoso',
            'status_kondisi' => 'normal',
            'kategori_usia' => 'dewasa',
        ], $ubah));
    }

    protected function buatKegiatan(): Kegiatan
    {
        return Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Uji',
            'tanggal_kegiatan' => now()->toDateString(),
            'status_kegiatan' => true,
            'waktu_dibuka' => now(),
        ]);
    }

    protected function buatPemeriksaan(Warga $warga, Kegiatan $kegiatan, array $ubah = []): Pemeriksaan
    {
        return $warga->pemeriksaan()->create(array_merge([
            'id_kegiatan' => $kegiatan->id_kegiatan,
            'kategori_usia_saat_periksa' => KategoriPemeriksaan::Dewasa,
            'tanggal_pemeriksaan' => now()->toDateString(),
        ], $ubah));
    }
}
