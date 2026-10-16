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
     * Tab formulir: kunci => ['label' => 'Rutin Bulanan', 'bagian' => ['pengukuran', 'tbc', ...]].
     *
     * @return array<string, array{label: string, bagian: array<int, string>}>
     */
    abstract public function tab(): array;

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
