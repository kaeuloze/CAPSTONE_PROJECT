<?php

namespace App\Services\Pemeriksaan\Form;

use App\Enums\JenisIbu;
use App\Enums\KategoriPemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\DefinisiForm;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;

/**
 * Form Pemeriksaan Ibu Hamil (Figma 430:927 "hasil rev" + foto Kartu Bantu Pemeriksaan Ibu Hamil/Nifas/Menyusui).
 * Tab "Ibu Hamil" | "Ibu Nifas" — tab yang tidak sesuai status ibu saat ini dikunci (disetujui).
 * Semua isian tercatat sebagai pemeriksaan Rutin (keputusan 2).
 */
class FormIbuHamil extends DefinisiForm
{
    /** Rentang usia kehamilan (minggu) sesuai kolom kartu kertas; disimpan apa adanya di usia_kehamilan_minggu. */
    public const USIA_KEHAMILAN = [
        '<4' => '< 4 minggu', '4-8' => '4 - 8 minggu', '8-12' => '8 - 12 minggu', '12-16' => '12 - 16 minggu',
        '16-20' => '16 - 20 minggu', '20-24' => '20 - 24 minggu', '24-28' => '24 - 28 minggu', '28-32' => '28 - 32 minggu',
        '32-36' => '32 - 36 minggu', '36-40' => '36 - 40 minggu',
    ];

    /** Ploting IMT sesuai kurva Buku KIA — dipilih kader (disetujui; tidak bisa dihitung karena butuh IMT sebelum hamil). */
    public const PLOTING_IMT = [
        'normal' => 'Normal (IMT sebelum hamil 18,5 – 24,9 kg/m²)',
        'kurus' => 'Risiko (IMT sebelum hamil < 18,5 kg/m²)',
        'gemuk' => 'Risiko (IMT sebelum hamil > 25 kg/m²)',
    ];

    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::IbuHamil;
    }

    public function judul(): string
    {
        return 'Pemeriksaan — Ibu Hamil / Nifas';
    }

    public function tab(): array
    {
        return [
            'hamil' => ['label' => 'Ibu Hamil', 'bagian' => ['kehamilan', 'pengukuran', 'ploting_imt', 'tbc', 'pelayanan', 'kelas']],
            'nifas' => ['label' => 'Ibu Nifas', 'bagian' => []],
        ];
    }

    public function bagianUmum(): array
    {
        return ['penyuluhan'];
    }

    public function tabNonaktif(Warga $warga, CarbonInterface $tanggalPeriksa): array
    {
        return ['nifas' => 'Status ibu saat ini: Ibu Hamil'];
    }

    public function keteranganAksi(): ?string
    {
        return 'Simpan dan Submit berlaku untuk tab sesuai status ibu saat ini (Ibu Hamil atau Ibu Nifas) — isian dikirim sebagai satu pemeriksaan.';
    }

    public function viewTambahan(): ?string
    {
        return 'pemeriksaan.form.ibu-hamil._hasil';
    }

    public function view(): string
    {
        return 'pemeriksaan.form.ibu-hamil';
    }

    protected function daftarBagian(): array
    {
        return [
            $this->bagianRentang('kehamilan', 'Pemeriksaan Sesuai Umur Kehamilan', self::USIA_KEHAMILAN),
            $this->bagianPengukuran(['berat_badan' => BagianUmum::angka(1, 300), 'lila' => BagianUmum::angka(5, 60)]),
            $this->bagianPlotingImt(),
            BagianUmum::tbc(),
            $this->bagianBumil('pelayanan', 'Pelayanan Kesehatan', [
                'jumlah_ttd_mms' => ['nullable', 'integer', 'between:0,500'],
                'status_konsumsi_ttd_rutin' => ['nullable', 'boolean'],
                'komposisi_mt_bumil_kek' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
                'status_rutin_mt_kek' => ['nullable', 'boolean'],
            ], ['status_konsumsi_ttd_rutin', 'status_rutin_mt_kek']),
            $this->bagianBumil('kelas', 'Kelas Ibu Hamil', ['ikut_kelas_ibu_hamil' => ['nullable', 'boolean']], ['ikut_kelas_ibu_hamil']),
            BagianUmum::penyuluhan(),
        ];
    }

    /** Jenis ibu yang dicatat di pemeriksaan_bumil.jenis_ibu. */
    protected function jenisIbu(): JenisIbu
    {
        return JenisIbu::Hamil;
    }

    /** Bagian yang tersimpan di tabel pemeriksaan_bumil (selalu menyertakan jenis_ibu). */
    protected function bagianBumil(string $kunci, string $judul, array $aturan, array $boolean = [], array $wajib = []): Bagian
    {
        $jenisIbu = ['jenis_ibu' => $this->jenisIbu()];

        return new Bagian(
            kunci: $kunci,
            judul: $judul,
            relasi: 'pemeriksaanBumil',
            aturan: $aturan,
            wajib: $wajib,
            boolean: $boolean,
            hitung: fn () => $jenisIbu,
        );
    }

    /** Pilihan rentang waktu (usia kehamilan / waktu setelah bersalin), wajib, disimpan di usia_kehamilan_minggu. */
    protected function bagianRentang(string $kunci, string $judul, array $pilihan): Bagian
    {
        return $this->bagianBumil($kunci, $judul, ['usia_kehamilan_minggu' => ['nullable', Rule::in(array_keys($pilihan))]], wajib: ['usia_kehamilan_minggu']);
    }

    /** Ploting IMT sesuai kurva Buku KIA — dipilih kader. */
    protected function bagianPlotingImt(): Bagian
    {
        return $this->bagianBumil('ploting_imt', 'Ploting IMT sesuai kurva Buku KIA', ['status_ploting_imt' => ['nullable', Rule::in(array_keys(self::PLOTING_IMT))]]);
    }

    /**
     * BB (+ LILA untuk ibu hamil) & TD, semuanya wajib. Ploting LILA & TD dihitung otomatis (disetujui) dan
     * disimpan di pemeriksaan_bumil lewat turunan; status TD juga di pengukuran_dasar.
     */
    protected function bagianPengukuran(array $aturanAngka): Bagian
    {
        $jenisIbu = ['jenis_ibu' => $this->jenisIbu()];
        $adaLila = array_key_exists('lila', $aturanAngka);

        return new Bagian(
            kunci: 'pengukuran',
            judul: 'Skrining Penimbangan dan Pengukuran',
            relasi: 'pengukuranDasar',
            aturan: $aturanAngka + BagianUmum::aturanTekananDarah(),
            wajib: [...array_keys($aturanAngka), 'td_sistolik', 'td_diastolik'],
            hitung: fn (array $n) => ['status_tekanan_darah' => Kalkulator::statusTekananDarah($n['td_sistolik'], $n['td_diastolik'])],
            uraikan: BagianUmum::uraikanTekananDarah(...),
            susun: BagianUmum::susunTekananDarah(...),
            turunan: fn (array $n) => ['pemeriksaanBumil' => $jenisIbu
                + ($adaLila ? ['status_ploting_lila' => Kalkulator::statusLila($n['lila'])] : [])
                + ['status_ploting_tekanan_darah' => Kalkulator::statusTekananDarah($n['td_sistolik'], $n['td_diastolik'])]],
        );
    }
}
