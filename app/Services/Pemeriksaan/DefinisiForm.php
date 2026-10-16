<?php

namespace App\Services\Pemeriksaan;

use App\Enums\KategoriPemeriksaan;

/**
 * Definisi satu formulir pemeriksaan per kategori (Remaja, Dewasa, Lansia, Ibu Hamil, Ibu Nifas).
 * Setiap form mendaftarkan tab, bagian (kartu), dan tampilan isiannya; penyimpanan, versi
 * antar-meja, dan aturan Simpan/Submit ditangani bersama oleh PemeriksaanService.
 */
abstract class DefinisiForm
{
    abstract public function kategori(): KategoriPemeriksaan;

    /** Judul halaman, mis. "Pemeriksaan — Remaja (6 - 18 Tahun)" (Figma). */
    abstract public function judul(): string;

    /**
     * Tab formulir: kunci => [
     *   'label' => 'Rutin Bulanan', 'bagian' => ['pengukuran', 'tbc'],
     *   'jenis' => JenisPemeriksaan (opsional; untuk peringatan interval 6 bulan / 1 tahun),
     *   'catatan' => teks di atas tombol Simpan/Submit saat tab ini dibuka (opsional),
     * ].
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public function tab(): array;

    /** Bagian yang tampil di bawah semua tab (mis. Penyuluhan & Rujukan). */
    public function bagianUmum(): array
    {
        return [];
    }

    /** Tab yang dinonaktifkan untuk warga tertentu: kunci tab => alasan. */
    public function tabNonaktif(\App\Models\Warga $warga): array
    {
        return [];
    }

    /** View tambahan di bawah semua bagian (mis. kotak "Hasil dihitung otomatis"), null bila tidak ada. */
    public function viewTambahan(): ?string
    {
        return null;
    }

    /** @return array<int, Bagian> */
    abstract protected function daftarBagian(): array;

    /** View Blade berisi isian tiap bagian, mis. "pemeriksaan.form.remaja". */
    abstract public function view(): string;

    /** @return array<string, Bagian> bagian berdasarkan kuncinya */
    public function bagian(): array
    {
        $hasil = [];
        foreach ($this->daftarBagian() as $bagian) {
            $hasil[$bagian->kunci] = $bagian;
        }

        return $hasil;
    }

    /** Aturan validasi seluruh isian untuk tombol Simpan (semua boleh kosong). */
    public function aturanValidasi(): array
    {
        $aturan = [];
        foreach ($this->bagian() as $bagian) {
            $aturan["bagian.{$bagian->kunci}"] = ['nullable', 'array'];
            foreach ($bagian->aturan as $kolom => $rules) {
                $aturan["bagian.{$bagian->kunci}.{$kolom}"] = $rules;
            }
        }

        return $aturan;
    }
}
