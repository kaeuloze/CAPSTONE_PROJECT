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

    /** Tab yang dinonaktifkan untuk warga tertentu pada tanggal pemeriksaan: kunci tab => alasan. */
    public function tabNonaktif(\App\Models\Warga $warga, \Carbon\CarbonInterface $tanggalPeriksa): array
    {
        return [];
    }

    /** Keterangan di atas tombol Simpan/Submit; null = bawaan ("berlaku untuk seluruh tab ..."). */
    public function keteranganAksi(): ?string
    {
        return null;
    }

    /** View tambahan di bawah semua bagian (mis. kotak "Hasil dihitung otomatis"), null bila tidak ada. */
    public function viewTambahan(): ?string
    {
        return null;
    }

    /**
     * Hasil utama untuk kolom "Ringkasan" di Riwayat per Warga (disetujui: jenis + hasil utama).
     * Relasi pengukuranDasar, skriningTbc, dan penyuluhanRujukan sebaiknya sudah di-eager-load.
     * Form lain boleh menambah hasil khususnya (mis. skor PUMA pada Lansia).
     *
     * @return array<int, string>
     */
    public function ringkasan(\App\Models\Pemeriksaan $pemeriksaan): array
    {
        $hasil = [];
        $ukur = $pemeriksaan->pengukuranDasar;
        if ($ukur?->status_imt) {
            $hasil[] = 'IMT '.$ukur->status_imt->label();
        }
        if ($ukur?->status_tekanan_darah) {
            $hasil[] = 'TD '.$ukur->status_tekanan_darah->label();
        }
        if (\App\Services\Pemeriksaan\Kalkulator::statusGulaDarah($ukur?->kadar_gula_darah) === \App\Enums\StatusRisiko::Berisiko) {
            $hasil[] = 'GDS Berisiko';
        }
        if ($pemeriksaan->skriningPuma?->status_ppok === \App\Enums\StatusPpok::Berisiko) {
            $hasil[] = 'Berisiko PPOK';
        }
        $bumil = $pemeriksaan->pemeriksaanBumil;
        if ($bumil?->usia_kehamilan_minggu) {
            $hasil[] = 'Usia kehamilan '.str_replace('-', '–', $bumil->usia_kehamilan_minggu).' minggu';
        }
        if ($bumil?->status_ploting_lila === \App\Enums\StatusLila::Kek) {
            $hasil[] = 'LILA KEK';
        }
        if ($pemeriksaan->skriningAks?->status_aks && $pemeriksaan->skriningAks->status_aks !== \App\Enums\StatusAks::Mandiri) {
            $hasil[] = 'AKS '.$pemeriksaan->skriningAks->status_aks->label();
        }
        if ($pemeriksaan->skriningSkilas && ($temuan = \App\Services\Pemeriksaan\Kalkulator::temuanSkilas($pemeriksaan->skriningSkilas->getAttributes())) !== []) {
            $hasil[] = 'SKILAS: '.count($temuan).' temuan';
        }
        if ($pemeriksaan->skriningTbc?->status_gejala_tbc === \App\Enums\StatusRisiko::Berisiko) {
            $hasil[] = 'Gejala TBC Berisiko';
        }
        if ($pemeriksaan->penyuluhanRujukan?->status_rujukan === \App\Enums\StatusRujukan::RujukPuskesmas) {
            $hasil[] = 'Dirujuk ke Puskesmas';
        }

        return $hasil;
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
