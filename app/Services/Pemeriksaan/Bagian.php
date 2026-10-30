<?php

namespace App\Services\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Models\Kegiatan;
use App\Models\Warga;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu "bagian" (kartu) formulir pemeriksaan yang tersimpan di satu tabel detail,
 * mis. "Skrining Penimbangan dan Pengukuran" → tabel pengukuran_dasar.
 *
 * Bagian adalah satuan penyimpanan antar-meja (keputusan 39: simpan per bagian):
 * setiap bagian punya "versi" sendiri, sehingga dua meja yang mengisi bagian berbeda
 * dari warga yang sama tidak saling menimpa.
 */
final class Bagian
{
    /**
     * @param  string  $kunci  nama unik bagian di formulir (dipakai di nama input)
     * @param  string  $judul  judul kartu, dipakai juga di pesan konflik
     * @param  string  $relasi  nama relasi hasOne di model Pemeriksaan
     * @param  array<string, array<int, mixed>>  $aturan  kolom => aturan validasi saat Simpan (semua boleh kosong)
     * @param  array<int, string>  $wajib  kolom yang wajib terisi saat Submit (tanda *)
     * @param  array<int, string>  $boolean  kolom centang (tidak dicentang = "Tidak")
     * @param  JenisPemeriksaan  $jenis  jenis pemeriksaan yang tercatat bila bagian ini terisi
     * @param  Closure|null  $hitung  fn(array $nilai, Warga, Kegiatan): array kolom otomatis (IMT, status, dll.)
     * @param  Closure|null  $uraikan  fn(array $kolomDb): array isian formulir — bila isian berbeda dari kolom database
     *                                 (mis. kolom "120/80" ditampilkan sebagai 2 kotak sistolik & diastolik)
     * @param  Closure|null  $susun  fn(array $isian): array kolom database — kebalikan dari $uraikan
     * @param  Closure|null  $berlaku  fn(Warga, CarbonInterface $tanggalPeriksa): bool — bagian hanya berlaku untuk
     *                                 warga tertentu (mis. Kadar HB hanya remaja putri, PUMA usia ≥ 40); bila false isiannya diabaikan
     * @param  array<int, string>  $wajibBilaTerisi  kolom yang wajib saat Submit HANYA bila bagian ini diisi
     *                                               (mis. status merokok bila PUMA dikerjakan)
     * @param  Closure|null  $turunan  fn(array $nilai, Warga, Kegiatan): array<relasi, array kolom> — kolom otomatis yang
     *                                 disimpan di tabel detail LAIN (mis. ploting LILA/TD ibu hamil di pemeriksaan_bumil
     *                                 dihitung dari isian pengukuran di pengukuran_dasar)
     * @param  bool  $catatBilaKosong  saat Submit / Edit Riwayat, bila tab bagian ini dilakukan tetapi semua centangnya
     *                                 "Tidak" (bagian kosong tidak tersimpan), tetap dicatat sebagai hasil "Tidak"
     *                                 (mis. Skrining TBC tanpa gejala = Normal, agar ikut terhitung di Dashboard)
     */
    public function __construct(
        public readonly string $kunci,
        public readonly string $judul,
        public readonly string $relasi,
        public readonly array $aturan,
        public readonly array $wajib = [],
        public readonly array $boolean = [],
        public readonly JenisPemeriksaan $jenis = JenisPemeriksaan::Rutin,
        public readonly ?Closure $hitung = null,
        public readonly ?Closure $uraikan = null,
        public readonly ?Closure $susun = null,
        public readonly ?Closure $berlaku = null,
        public readonly array $wajibBilaTerisi = [],
        public readonly ?Closure $turunan = null,
        public readonly bool $catatBilaKosong = false,
    ) {
    }

    /** @return array<int, string> kolom yang diisi kader di bagian ini */
    public function kolom(): array
    {
        return array_keys($this->aturan);
    }

    /**
     * Nilai tersimpan di database, dirapikan agar bisa dibandingkan dengan isian formulir.
     *
     * @return array<string, ?string>
     */
    public function nilaiTersimpan(?Model $baris): array
    {
        $mentah = $baris?->getAttributes() ?? [];
        if ($this->uraikan) {
            $mentah = ($this->uraikan)($mentah) + $mentah;
        }
        $hasil = [];
        foreach ($this->kolom() as $kolom) {
            $hasil[$kolom] = $this->rapikan($kolom, $mentah[$kolom] ?? null);
        }

        return $hasil;
    }

    /**
     * Isian formulir dari kader, dirapikan dengan aturan yang sama.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, ?string>
     */
    public function nilaiIsian(array $input): array
    {
        $hasil = [];
        foreach ($this->kolom() as $kolom) {
            $hasil[$kolom] = $this->rapikan($kolom, $input[$kolom] ?? null);
        }

        return $hasil;
    }

    /** Mengubah isian formulir menjadi kolom database (setelah dirapikan). */
    public function keDatabase(array $nilai): array
    {
        return $this->susun ? ($this->susun)($nilai) : $nilai;
    }

    public function berlakuUntuk(Warga $warga, CarbonInterface $tanggalPeriksa): bool
    {
        return $this->berlaku === null || ($this->berlaku)($warga, $tanggalPeriksa);
    }

    /** Penanda versi = sidik jari isi bagian. Berubah bila ada kader lain yang menyimpan bagian ini. */
    public function versi(?Model $baris): string
    {
        return md5(json_encode($this->nilaiTersimpan($baris)));
    }

    /** True bila ada isian yang tidak kosong (centang "Ya" atau angka/teks terisi). */
    public function terisi(array $nilai): bool
    {
        foreach ($nilai as $kolom => $isi) {
            if (in_array($kolom, $this->boolean, true) ? $isi === '1' : $isi !== null) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> kolom wajib yang masih kosong */
    public function wajibKosong(array $nilai): array
    {
        $wajib = $this->terisi($nilai) ? [...$this->wajib, ...$this->wajibBilaTerisi] : $this->wajib;

        return array_values(array_filter($wajib, fn (string $kolom) => ($nilai[$kolom] ?? null) === null));
    }

    private function rapikan(string $kolom, mixed $nilai): ?string
    {
        if (in_array($kolom, $this->boolean, true)) {
            return filter_var($nilai, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if ($nilai instanceof \BackedEnum) {
            $nilai = $nilai->value;
        }
        if ($nilai === null) {
            return null;
        }

        $teks = trim(preg_replace('/\s+/u', ' ', (string) $nilai));
        if ($teks === '') {
            return null;
        }
        // Angka desimal: "45.50" (database) dan "45.5" (isian) dianggap sama.
        if (is_numeric($teks) && str_contains($teks, '.')) {
            $teks = rtrim(rtrim($teks, '0'), '.');
        }

        return $teks;
    }
}
