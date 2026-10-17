<?php

namespace Database\Seeders;

use App\Enums\KategoriPemeriksaan;
use App\Enums\KategoriUsia;
use App\Enums\StatusPemeriksaan;
use App\Models\AsiEksklusif;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\Pemeriksaan;
use App\Models\Penyakit;
use App\Models\RiwayatPtm;
use App\Models\Vaksin;
use App\Models\Vaksinasi;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormIbuHamil;
use App\Services\Pemeriksaan\Form\FormIbuNifas;
use App\Services\Pemeriksaan\Form\FormLansia;
use App\Services\Pemeriksaan\KatalogForm;
use App\Services\Pemeriksaan\PemeriksaanService;
use Carbon\CarbonImmutable;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data DUMMY untuk uji coba di laptop pengembang (bukan data warga asli) — disetujui pengguna 17 Okt 2026.
 *
 * Isi: 250 warga semua kategori (balita, remaja, dewasa, lansia, ibu hamil/nifas/menyusui) beserta riwayat PTM;
 * 5 kegiatan bulanan yang sudah selesai (5 bulan terakhir) dengan presensi dan pemeriksaan yang sudah di-Submit,
 * screening 6-bulanan & tahunan, vaksinasi & ASI balita; serta 1 kegiatan yang sedang berlangsung hari ini
 * (sebagian warga hadir, sebagian pemeriksaan selesai/draft/belum dimulai).
 *
 * Isian pemeriksaan disimpan lewat mesin form aplikasi (PemeriksaanService), sehingga status otomatis
 * (IMT, TD, PUMA, AKS, ploting LILA, dll.) dihitung dengan aturan yang sama seperti saat kader mengisi.
 * Hasil selalu sama setiap dijalankan (faker memakai seed tetap).
 */
class DataDummySeeder extends Seeder
{
    private const JUMLAH = [
        'balita' => 32, 'bayi' => 8, 'remaja' => 45, 'dewasa' => 90,
        'hamil' => 10, 'nifas' => 5, 'menyusui' => 5, 'lansia' => 55,
    ]; // total 250

    /** Jadwal imunisasi dasar: nama vaksin => usia pemberian (bulan). */
    private const JADWAL_VAKSIN = [
        'Hepatitis B (HB-0)' => 0, 'BCG' => 1, 'Polio 1' => 1, 'DPT-HB-Hib 1' => 2, 'Polio 2' => 2,
        'DPT-HB-Hib 2' => 3, 'Polio 3' => 3, 'DPT-HB-Hib 3' => 4, 'Polio 4' => 4, 'IPV' => 4, 'Campak / MR' => 9,
    ];

    private const TOPIK = [
        'Gizi seimbang', 'Cuci tangan pakai sabun', 'Pencegahan hipertensi', 'Bahaya merokok', 'Aktivitas fisik teratur',
        'Pencegahan diabetes', 'Kesehatan reproduksi remaja', 'Tablet tambah darah', 'Pencegahan TBC', 'Pencegahan jatuh pada lansia',
    ];

    private Generator $faker;

    private CarbonImmutable $hariIni;

    private PemeriksaanService $layanan;

    private KatalogForm $katalog;

    /** @var array<int, array{warga: Warga, jenis: string, kondisi: array<int, ?string>, mulai: int}> */
    private array $daftar = [];

    public function run(): void
    {
        $this->faker = FakerFactory::create('id_ID');
        $this->faker->seed(20261017);
        $this->hariIni = CarbonImmutable::today();
        $this->layanan = app(PemeriksaanService::class);
        $this->katalog = app(KatalogForm::class);

        DB::transaction(function () {
            $kegiatan = $this->buatKegiatan();
            $this->buatWarga(count($kegiatan));
            foreach ($kegiatan as $urutan => $k) {
                $this->isiKegiatan($k, $urutan, $urutan === count($kegiatan) - 1);
            }
            $this->isiVaksinDanAsi($kegiatan);
        });
    }

    // ------------------------------------------------------------------ kegiatan & warga

    /** @return array<int, Kegiatan> 5 kegiatan selesai (tanggal 12 tiap bulan) + 1 berlangsung hari ini */
    private function buatKegiatan(): array
    {
        $hasil = [];
        for ($i = 5; $i >= 1; $i--) {
            $tanggal = $this->hariIni->subMonthsNoOverflow($i)->setDay(12);
            $hasil[] = Kegiatan::create([
                'nama_kegiatan' => 'Posyandu '.$tanggal->locale('id')->translatedFormat('F Y'),
                'tanggal_kegiatan' => $tanggal->toDateString(),
                'status_kegiatan' => false,
                'waktu_dibuka' => $tanggal->setTime(8, 0),
            ]);
        }
        $hasil[] = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu '.$this->hariIni->locale('id')->translatedFormat('F Y'),
            'tanggal_kegiatan' => $this->hariIni->toDateString(),
            'status_kegiatan' => true,
            'waktu_dibuka' => now(),
        ]);

        return $hasil;
    }

    private function buatWarga(int $jumlahKegiatan): void
    {
        $penyakit = Penyakit::query()->pluck('id_penyakit')->all();
        $urut = 0;

        foreach (self::JUMLAH as $jenis => $jumlah) {
            for ($i = 0; $i < $jumlah; $i++) {
                $urut++;
                $perempuan = in_array($jenis, ['hamil', 'nifas', 'menyusui'], true) || $this->faker->boolean(52);
                $lahir = $this->tanggalLahir($jenis);
                $warga = Warga::create([
                    'nik' => $this->nik($lahir, $perempuan, $urut),
                    'nama_warga' => $this->nama($perempuan),
                    'jenis_kelamin' => ! $perempuan,
                    'tanggal_lahir' => $lahir->toDateString(),
                    'no_hp' => $this->faker->boolean(70) ? $this->faker->numerify('08##########') : null,
                    'alamat' => sprintf('Tegalyoso RT %02d / RW %02d, Banyuraden, Gamping, Sleman', $this->faker->numberBetween(1, 6), $this->faker->numberBetween(1, 3)),
                    'status_kondisi' => in_array($jenis, ['hamil', 'nifas', 'menyusui'], true) ? $jenis : 'normal',
                ]);

                if (in_array($jenis, ['dewasa', 'lansia', 'hamil'], true) && $this->faker->boolean($jenis === 'lansia' ? 45 : 25)) {
                    foreach ($this->faker->randomElements($penyakit, $this->faker->numberBetween(1, 2)) as $idPenyakit) {
                        RiwayatPtm::create([
                            'nik' => $warga->nik, 'id_penyakit' => $idPenyakit,
                            'sumber_riwayat' => 'kartu_bantu', 'tanggal_dicatat' => $this->hariIni->subMonthsNoOverflow(5)->setDay(12)->toDateString(),
                        ]);
                    }
                }

                $this->daftar[] = [
                    'warga' => $warga,
                    'jenis' => $jenis,
                    'kondisi' => $this->kondisiPerKegiatan($jenis, $jumlahKegiatan),
                    'mulai' => $this->faker->numberBetween(4, 16), // usia kehamilan (minggu) pada kegiatan pertama
                ];
            }
        }
    }

    private function tanggalLahir(string $jenis): CarbonImmutable
    {
        $hariUsia = match ($jenis) {
            'bayi' => $this->faker->numberBetween(10, 170),          // 0–5 bulan → ASI tampil
            'balita' => $this->faker->numberBetween(190, 4 * 365 + 300),
            'remaja' => $this->faker->numberBetween(5 * 365 + 20, 17 * 365 + 300),
            'dewasa' => $this->faker->numberBetween(18 * 365 + 20, 59 * 365 + 300),
            'hamil', 'nifas', 'menyusui' => $this->faker->numberBetween(20 * 365, 38 * 365),
            'lansia' => $this->faker->numberBetween(60 * 365 + 20, 85 * 365),
        };

        return $this->hariIni->subDays($hariUsia);
    }

    /** NIK dummy format Sleman (3404) — Gamping (07): DDMMYY (+40 untuk perempuan) + nomor urut. */
    private function nik(CarbonImmutable $lahir, bool $perempuan, int $urut): string
    {
        return '340407'.sprintf('%02d', $lahir->day + ($perempuan ? 40 : 0)).$lahir->format('my').sprintf('%04d', $urut);
    }

    private function nama(bool $perempuan): string
    {
        $depan = $perempuan ? $this->faker->firstNameFemale() : $this->faker->firstNameMale();
        $belakang = $perempuan ? $this->faker->lastNameFemale() : $this->faker->lastNameMale();

        return preg_replace("/[^A-Za-z .,'-]/", '', $depan.' '.$belakang);
    }

    /**
     * Kondisi ibu pada tiap kegiatan: hamil → selalu hamil; nifas → hamil 3 bulan pertama lalu nifas;
     * menyusui → nifas (pasca-bersalin) sepanjang periode. Selain itu null.
     *
     * @return array<int, ?string>
     */
    private function kondisiPerKegiatan(string $jenis, int $jumlah): array
    {
        return array_map(fn (int $i) => match ($jenis) {
            'hamil' => 'hamil',
            'nifas' => $i < 3 ? 'hamil' : 'nifas',
            'menyusui' => 'nifas',
            default => null,
        }, range(0, $jumlah - 1));
    }

    // ------------------------------------------------------------------ presensi & pemeriksaan

    private function isiKegiatan(Kegiatan $kegiatan, int $urutan, bool $berlangsung): void
    {
        $tanggal = CarbonImmutable::parse($kegiatan->tanggal_kegiatan);

        foreach ($this->daftar as $data) {
            /** @var Warga $warga */
            $warga = $data['warga'];
            if (CarbonImmutable::parse($warga->tanggal_lahir)->greaterThan($tanggal)) {
                continue; // belum lahir
            }

            $peluang = match ($data['jenis']) {
                'balita', 'bayi', 'hamil', 'nifas', 'menyusui' => 85,
                'lansia' => 72,
                default => 60,
            };
            $hadir = $this->faker->boolean($berlangsung ? (int) round($peluang * 0.55) : $peluang);

            if (! $hadir && $berlangsung) {
                continue; // kegiatan masih berlangsung: belum ada catatan
            }
            Kehadiran::create([
                'nik' => $warga->nik, 'id_kegiatan' => $kegiatan->id_kegiatan, 'status_kehadiran' => $hadir,
                'waktu_kehadiran' => $hadir ? $tanggal->setTime(8, 0)->addMinutes($this->faker->numberBetween(0, 180)) : null,
            ]);
            if (! $hadir) {
                continue;
            }

            $kategori = $this->kategoriPeriksa($warga, $tanggal, $data['kondisi'][$urutan]);
            if ($kategori === null) {
                continue; // balita: hanya hadir + vaksinasi
            }

            // Kegiatan berlangsung: 35% selesai, 20% draft (baru penimbangan), sisanya belum diperiksa.
            $nasib = $berlangsung ? $this->faker->numberBetween(1, 100) : 1;
            if ($nasib > 55) {
                continue;
            }
            $this->periksa($kegiatan, $warga, $kategori, $this->isian($kategori, $warga, $tanggal, $urutan, $data), $nasib <= 35);
        }
    }

    private function kategoriPeriksa(Warga $warga, CarbonImmutable $tanggal, ?string $kondisi): ?KategoriPemeriksaan
    {
        return match ($kondisi) {
            'hamil' => KategoriPemeriksaan::IbuHamil,
            'nifas' => KategoriPemeriksaan::IbuNifas,
            default => match (KategoriUsia::dariTanggalLahir(CarbonImmutable::parse($warga->tanggal_lahir), $tanggal)) {
                KategoriUsia::Balita => null,
                KategoriUsia::Remaja => KategoriPemeriksaan::Remaja,
                KategoriUsia::Dewasa => KategoriPemeriksaan::Dewasa,
                KategoriUsia::Lansia => KategoriPemeriksaan::Lansia,
            },
        };
    }

    /** Menyimpan isian lewat mesin form aplikasi; Submit bila $selesai (draft: hanya bagian penimbangan). */
    private function periksa(Kegiatan $kegiatan, Warga $warga, KategoriPemeriksaan $kategori, array $isian, bool $selesai): void
    {
        $form = $this->katalog->untuk($kategori);
        $pemeriksaan = Pemeriksaan::create([
            'nik' => $warga->nik, 'id_kegiatan' => $kegiatan->id_kegiatan, 'kategori_usia_saat_periksa' => $kategori,
            'tanggal_pemeriksaan' => $kegiatan->tanggal_kegiatan, 'status_pemeriksaan' => StatusPemeriksaan::Draft,
        ]);
        if (! $selesai) {
            $isian = array_intersect_key($isian, array_flip(['pengukuran', 'kehamilan', 'waktu']));
        }

        $versi = [];
        foreach ($form->bagian() as $kunci => $bagian) {
            $versi[$kunci] = $bagian->versi($pemeriksaan->{$bagian->relasi}()->first());
        }
        $this->layanan->terapkanIsian($pemeriksaan, $kegiatan, $warga, $form, $isian, $versi, array_keys($isian));

        if ($selesai) {
            $this->layanan->tandaiSelesai($pemeriksaan, $form, $warga);
        }
    }

    // ------------------------------------------------------------------ isian per kategori

    private function isian(KategoriPemeriksaan $kategori, Warga $warga, CarbonImmutable $tanggal, int $urutan, array $data): array
    {
        $usia = (int) CarbonImmutable::parse($warga->tanggal_lahir)->diffInYears($tanggal);
        $lakiLaki = (bool) $warga->jenis_kelamin;
        $penyuluhan = $this->penyuluhan();

        return match ($kategori) {
            KategoriPemeriksaan::Remaja => array_filter([
                'pengukuran' => $this->ukur($lakiLaki, 105 + min($usia - 5, 12) * 5, 8, false, false),
                'tbc' => $this->tbc(),
                'sensorik' => $urutan === 1 ? $this->sensorik() : null,
                'hb' => $urutan === 3 && ! $lakiLaki ? ['kadar_hb' => (string) $this->faker->randomFloat(1, 10.5, 14.5)] : null,
                'penyuluhan' => $penyuluhan,
            ]),
            KategoriPemeriksaan::Dewasa => array_filter([
                'pengukuran' => $this->ukur($lakiLaki, $lakiLaki ? 166 : 154, 30, true, true),
                'tbc' => $this->tbc(),
                'sensorik' => $urutan === 1 ? $this->sensorik() : null,
                'puma' => $urutan === 3 && $usia >= 40 && $this->faker->boolean(70) ? $this->puma($lakiLaki) : null,
                'penyuluhan' => $penyuluhan,
            ]),
            KategoriPemeriksaan::Lansia => array_filter([
                'pengukuran' => $this->ukur($lakiLaki, $lakiLaki ? 162 : 150, 45, true, true),
                'tbc' => $this->tbc(),
                'sensorik' => $urutan === 1 ? $this->sensorik() : null,
                'puma' => $urutan === 3 ? $this->puma($lakiLaki) : null,
                'aks' => $urutan === 3 ? $this->aks($usia) : null,
                'skilas' => $urutan === 3 ? $this->skilas() : null,
                'penyuluhan' => $penyuluhan,
            ]),
            KategoriPemeriksaan::IbuHamil => $this->ibuHamil(($data['jenis'] === 'nifas' ? 26 : $data['mulai']) + $urutan * 4, $penyuluhan),
            KategoriPemeriksaan::IbuNifas => $this->ibuNifas($data['jenis'] === 'menyusui' ? $urutan + 4 : $urutan - 3, $penyuluhan),
        };
    }

    /** BB/TB/LP/TD (+LILA & GDS); $risikoTd = peluang (%) tekanan darah tinggi. */
    private function ukur(bool $lakiLaki, int $tbDasar, int $risikoTd, bool $lila, bool $gds): array
    {
        $tb = $tbDasar + $this->faker->numberBetween(-8, 10);
        $imt = match (true) {
            $this->faker->boolean(12) => $this->faker->randomFloat(1, 15.5, 18.3),
            $this->faker->boolean(30) => $this->faker->randomFloat(1, 25.2, 29.5),
            default => $this->faker->randomFloat(1, 18.6, 24.8),
        };
        $bb = round($imt * ($tb / 100) ** 2, 1);
        $tinggi = $this->faker->boolean($risikoTd);
        $hasil = [
            'berat_badan' => (string) $bb,
            'tinggi_badan' => (string) $tb,
            'lingkar_perut' => (string) round($imt * 3.3 + $this->faker->numberBetween(-4, 6), 1),
            'td_sistolik' => (string) ($tinggi ? $this->faker->numberBetween(130, 165) : $this->faker->numberBetween(100, 128)),
            'td_diastolik' => (string) ($tinggi ? $this->faker->numberBetween(82, 100) : $this->faker->numberBetween(65, 83)),
        ];
        if ($lila) {
            $hasil['lila'] = (string) $this->faker->randomFloat(1, 22.5, 33);
        }
        if ($gds) {
            $hasil['kadar_gula_darah'] = $this->faker->boolean(45)
                ? (string) ($this->faker->boolean(15) ? $this->faker->numberBetween(200, 320) : $this->faker->numberBetween(80, 190))
                : null;
        }

        return $hasil;
    }

    private function tbc(): array
    {
        $acak = $this->faker->numberBetween(1, 100);

        return [
            'batuk_terus_menerus' => $acak > 92 ? '1' : '0',
            'demam_2_minggu' => $acak > 96 ? '1' : '0',
            'bb_tidak_stabil' => $acak > 97 ? '1' : '0',
            'kontak_pasien_tbc' => $this->faker->boolean(3) ? '1' : '0',
        ];
    }

    private function sensorik(): array
    {
        $pilih = fn () => $this->faker->boolean(90) ? 'normal' : 'gangguan';

        return ['mata_kanan' => $pilih(), 'mata_kiri' => $pilih(), 'telinga_kanan' => $pilih(), 'telinga_kiri' => $pilih()];
    }

    private function puma(bool $lakiLaki): array
    {
        $ya = fn (int $peluang) => $this->faker->boolean($peluang) ? '1' : '0';

        return [
            'skor_merokok' => (string) ($lakiLaki ? $this->faker->randomElement([0, 0, 0, 1, 1, 2]) : 0),
            'gejala_napas_pendek' => $ya(25), 'gejala_dahak' => $ya(20), 'gejala_batuk_tanpa_flu' => $ya(20), 'pernah_spirometri' => $ya(8),
        ];
    }

    private function aks(int $usia): array
    {
        $skor = array_map('strval', FormLansia::SKOR_MAKS_AKS);
        $kurang = match (true) {
            $this->faker->boolean($usia >= 75 ? 40 : 20) => $this->faker->numberBetween(1, 8),
            $this->faker->boolean(5) => $this->faker->numberBetween(9, 15),
            default => 0,
        };
        $kolom = array_keys($skor);
        while ($kurang > 0) {
            $k = $this->faker->randomElement($kolom);
            if ((int) $skor[$k] > 0) {
                $skor[$k] = (string) ((int) $skor[$k] - 1);
                $kurang--;
            }
            if (array_sum(array_map('intval', $skor)) === 0) {
                break;
            }
        }

        return $skor;
    }

    private function skilas(): array
    {
        $kolom = ['orientasi_waktu_tempat', 'mengulang_tiga_kata', 'tes_berdiri_kursi', 'bb_berkurang', 'hilang_nafsu_makan',
            'lila_kurang', 'masalah_penglihatan', 'tes_melihat', 'tes_berbisik', 'perasaan_sedih', 'kurang_minat'];
        $hasil = [];
        foreach ($kolom as $k) {
            $hasil[$k] = $this->faker->boolean(12) ? '1' : '0';
        }

        return $hasil + ['belum_imunisasi_covid' => $this->faker->boolean(20) ? '1' : '0'];
    }

    private function penyuluhan(): array
    {
        return [
            'topik_penyuluhan' => $this->faker->boolean(65) ? $this->faker->randomElement(self::TOPIK) : null,
            'dirujuk' => $this->faker->boolean(8) ? '1' : '0',
        ];
    }

    private function ibuHamil(int $minggu, array $penyuluhan): array
    {
        $rentang = collect(array_keys(FormIbuHamil::USIA_KEHAMILAN))
            ->first(fn ($k) => $minggu < (int) (str_contains($k, '-') ? explode('-', $k)[1] : 4)) ?? '36-40';
        $lila = $this->faker->boolean(15) ? $this->faker->randomFloat(1, 20.5, 23.4) : $this->faker->randomFloat(1, 23.5, 30);
        $kek = $lila < 23.5;
        $tinggi = $this->faker->boolean(12);

        return [
            'kehamilan' => ['usia_kehamilan_minggu' => $rentang],
            'pengukuran' => [
                'berat_badan' => (string) $this->faker->randomFloat(1, 48, 75), 'lila' => (string) $lila,
                'td_sistolik' => (string) ($tinggi ? $this->faker->numberBetween(130, 150) : $this->faker->numberBetween(100, 125)),
                'td_diastolik' => (string) ($tinggi ? $this->faker->numberBetween(85, 95) : $this->faker->numberBetween(65, 80)),
            ],
            'ploting_imt' => ['status_ploting_imt' => $this->faker->randomElement(['normal', 'normal', 'normal', 'kurus', 'gemuk'])],
            'tbc' => $this->tbc(),
            'pelayanan' => [
                'jumlah_ttd_mms' => $this->faker->boolean(85) ? '30' : null,
                'status_konsumsi_ttd_rutin' => $this->faker->boolean(70) ? '1' : '0',
                'komposisi_mt_bumil_kek' => $kek ? 'Biskuit MT ibu hamil 2 keping/hari' : null,
                'status_rutin_mt_kek' => $kek && $this->faker->boolean(60) ? '1' : '0',
            ],
            'kelas' => ['ikut_kelas_ibu_hamil' => $this->faker->boolean(40) ? '1' : '0'],
            'penyuluhan' => $penyuluhan,
        ];
    }

    private function ibuNifas(int $kunjungan, array $penyuluhan): array
    {
        $waktu = array_keys(FormIbuNifas::WAKTU_NIFAS);
        $tinggi = $this->faker->boolean(10);

        return [
            'waktu' => ['usia_kehamilan_minggu' => $waktu[max(0, min($kunjungan, count($waktu) - 1))]],
            'pengukuran' => [
                'berat_badan' => (string) $this->faker->randomFloat(1, 46, 70),
                'td_sistolik' => (string) ($tinggi ? $this->faker->numberBetween(130, 150) : $this->faker->numberBetween(100, 125)),
                'td_diastolik' => (string) ($tinggi ? $this->faker->numberBetween(85, 95) : $this->faker->numberBetween(65, 80)),
            ],
            'ploting_imt' => ['status_ploting_imt' => $this->faker->randomElement(['normal', 'normal', 'kurus', 'gemuk'])],
            'tbc' => $this->tbc(),
            'pelayanan' => [
                'jumlah_kapsul_vit_a' => $kunjungan <= 1 ? '2' : null,
                'status_rutin_vit_a' => $kunjungan <= 1 && $this->faker->boolean(80) ? '1' : '0',
                'status_menyusui' => $this->faker->boolean(90) ? '1' : '0',
                'status_kb_pascasalin' => $this->faker->boolean(50) ? '1' : '0',
            ],
            'penyuluhan' => $penyuluhan,
        ];
    }

    // ------------------------------------------------------------------ vaksinasi & ASI balita

    /** @param array<int, Kegiatan> $kegiatan */
    private function isiVaksinDanAsi(array $kegiatan): void
    {
        $vaksin = Vaksin::query()->pluck('id_vaksin', 'nama_vaksin');

        foreach ($this->daftar as $data) {
            if (! in_array($data['jenis'], ['balita', 'bayi'], true)) {
                continue;
            }
            /** @var Warga $warga */
            $warga = $data['warga'];
            $lahir = CarbonImmutable::parse($warga->tanggal_lahir);
            $hadirDi = Kehadiran::query()->where('nik', $warga->nik)->where('status_kehadiran', true)
                ->pluck('id_kegiatan')->all();
            $kegiatanHadir = array_values(array_filter($kegiatan, fn (Kegiatan $k) => in_array($k->id_kegiatan, $hadirDi, true)));
            if ($kegiatanHadir === []) {
                continue;
            }

            foreach (self::JADWAL_VAKSIN as $nama => $bulan) {
                $tanggal = $lahir->addMonthsNoOverflow($bulan)->addDays($this->faker->numberBetween(0, $bulan === 0 ? 6 : 20));
                if (! isset($vaksin[$nama]) || $tanggal->greaterThan($this->hariIni) || $this->faker->boolean(8)) {
                    continue; // belum waktunya / terlewat
                }
                // Dicatat pada kegiatan pertama setelah tanggal vaksin di mana balita hadir; vaksin lama disalin dari Buku KIA
                // pada kegiatan hadir pertama.
                $dicatat = collect($kegiatanHadir)->first(fn (Kegiatan $k) => CarbonImmutable::parse($k->tanggal_kegiatan)->greaterThanOrEqualTo($tanggal))
                    ?? null;
                if ($dicatat === null) {
                    continue; // diberikan setelah kunjungan terakhir; belum tercatat di posyandu
                }
                Vaksinasi::create([
                    'nik' => $warga->nik, 'id_vaksin' => $vaksin[$nama], 'id_kegiatan' => $dicatat->id_kegiatan,
                    'status_vaksinasi' => true, 'tanggal_vaksinasi' => $tanggal->toDateString(),
                ]);
            }

            foreach ($kegiatanHadir as $k) {
                $tanggalK = CarbonImmutable::parse($k->tanggal_kegiatan);
                $bulanKe = (int) $lahir->diffInMonths($tanggalK);
                if ($bulanKe > (int) config('posyandu.vaksinasi.asi_bulan_maks')
                    || AsiEksklusif::query()->where('nik', $warga->nik)->where('bulan_ke', $bulanKe)->exists()) {
                    continue;
                }
                AsiEksklusif::create([
                    'nik' => $warga->nik, 'bulan_ke' => $bulanKe, 'tanggal_dicatat' => $tanggalK->toDateString(),
                    'status_asi' => $this->faker->randomElement(['ya', 'ya', 'ya', 'ya', 'ya', 'tidak', 'tidak_recall', 'tidak_recall']),
                ]);
            }
        }
    }
}
