<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusImt;
use App\Enums\StatusPpok;
use App\Enums\StatusRisiko;
use App\Enums\StatusTekananDarah;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\SkriningPuma;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormDewasa;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Form Pemeriksaan Dewasa (Figma 430:65, 430:201, 430:304).
 */
class FormDewasaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-16 08:00:00'));
        $this->kader = $this->buatKader();
        $this->kegiatan = $this->kegiatanBaru('2026-10-16');
    }

    private function kegiatanBaru(string $tanggal, bool $berlangsung = true): Kegiatan
    {
        return Kegiatan::create([
            'nama_kegiatan' => "Posyandu {$tanggal}", 'tanggal_kegiatan' => $tanggal,
            'status_kegiatan' => $berlangsung, 'waktu_dibuka' => now(),
        ]);
    }

    private function dewasa(string $tanggalLahir = '1981-05-01', bool $lakiLaki = false, string $nik = '3404000000000001'): Warga
    {
        $warga = $this->buatWarga($nik, ['nama_warga' => 'Sari', 'tanggal_lahir' => $tanggalLahir, 'jenis_kelamin' => $lakiLaki]);
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(Warga $warga): array
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->where('id_kegiatan', $this->kegiatan->id_kegiatan)->first();
        $hasil = [];
        foreach ((new FormDewasa)->bagian() as $kunci => $b) {
            $hasil[$kunci] = $b->versi($p?->{$b->relasi}()->first());
        }

        return $hasil;
    }

    private function kirim(Warga $warga, array $isian, string $aksi = 'simpan')
    {
        return $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => $isian, 'versi' => $this->versi($warga), 'diubah' => array_keys($isian), 'aksi' => $aksi,
        ]);
    }

    private function rutin(array $ubah = []): array
    {
        return array_merge(['berat_badan' => '60', 'tinggi_badan' => '160', 'lingkar_perut' => '78', 'lila' => '26.5',
            'td_sistolik' => '120', 'td_diastolik' => '80', 'kadar_gula_darah' => null], $ubah);
    }

    private function puma(string $merokok = '0', array $ya = []): array
    {
        $hasil = ['skor_merokok' => $merokok];
        foreach (['gejala_napas_pendek', 'gejala_dahak', 'gejala_batuk_tanpa_flu', 'pernah_spirometri'] as $k) {
            $hasil[$k] = in_array($k, $ya, true) ? '1' : '0';
        }

        return $hasil;
    }

    // ---------- Tampilan ----------

    public function test_form_dewasa_sesuai_figma(): void
    {
        $warga = $this->dewasa();

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()
            ->assertSee('Pemeriksaan — Dewasa (18 - 59 Tahun)')->assertSee('Dewasa · 45 th')
            ->assertSeeInOrder(['Pemeriksaan Rutin Bulanan', 'Pemeriksaan 6 Bulan Sekali', 'Pemeriksaan Tahunan'])
            ->assertSeeInOrder(['Berat badan (kg)', 'Tinggi Badan (cm)', 'Lingkar perut (cm)', 'LILA (cm)', 'Tekanan darah (mmHg)', 'Kadar gula darah (mg/dl) (Nakes)'])
            ->assertSee('id="isian-pengukuran-lila"', false)
            ->assertSee('Skrining Gejala TBC')
            ->assertSee('Tes Penglihatan (Hitung Jari)')->assertSee('Tes Pendengaran (Berbisik)')
            ->assertSee('Skrining PPOK PUMA (Khusus Usia ≥ 40 tahun)')
            ->assertSee('Perempuan → skor 0')->assertSee('45 tahun → skor 0')
            ->assertSee('Batuk walau tidak sedang flu?')
            ->assertSee('Topik penyuluhan')->assertSee('Rujukan ke Puskesmas')
            ->assertSee('Hasil dihitung otomatis (Kalkulator IMT)');
    }

    public function test_lila_wajib_tetapi_gula_darah_tidak(): void
    {
        $html = $this->actingAs($this->kader)->get(route('pemeriksaan.form', $this->dewasa()->nik))->getContent();

        $this->assertMatchesRegularExpression('/id="isian-pengukuran-lila"[^>]*data-wajib/s', $html);
        $this->assertDoesNotMatchRegularExpression('/id="isian-pengukuran-kadar_gula_darah"[^>]*data-wajib/s', $html);
    }

    public function test_tab_tahunan_nonaktif_di_bawah_40_tahun_dan_aktif_tepat_40(): void
    {
        $muda = $this->dewasa('1987-10-17', nik: '3404000000000001'); // 39 tahun (ulang tahun besok)
        $pas = $this->dewasa('1986-10-16', nik: '3404000000000002');  // tepat 40 tahun hari ini

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $muda->nik))
            ->assertSee('disabled title="Khusus usia 40 tahun ke atas"', false)->assertDontSee('Skrining PPOK PUMA');
        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $pas->nik))
            ->assertDontSee('Khusus usia 40 tahun ke atas')->assertSee('Skrining PPOK PUMA');
    }

    // ---------- Simpan & status otomatis ----------

    public function test_simpan_rutin_dengan_lila_dan_gula_darah(): void
    {
        $warga = $this->dewasa(lakiLaki: true);

        $this->kirim($warga, ['pengukuran' => $this->rutin(['berat_badan' => '75', 'lingkar_perut' => '91', 'td_sistolik' => '128', 'td_diastolik' => '86', 'kadar_gula_darah' => '145'])])
            ->assertSessionHas('sukses');

        $ukur = Pemeriksaan::sole()->pengukuranDasar;
        $this->assertSame('26.5', $ukur->getRawOriginal('lila'));
        $this->assertSame('145.0', $ukur->getRawOriginal('kadar_gula_darah'));
        $this->assertSame('128/86', $ukur->tekanan_darah);
        $this->assertSame(StatusImt::Obesitas, $ukur->status_imt);                // 75 / 1,6² = 29,3
        $this->assertSame(StatusTekananDarah::Tinggi, $ukur->status_tekanan_darah); // diastolik ≥ 85
        $this->assertSame(StatusRisiko::Berisiko, $ukur->status_lingkar_perut);     // laki-laki > 90
    }

    public function test_submit_butuh_lila(): void
    {
        $warga = $this->dewasa();

        $this->kirim($warga, ['pengukuran' => $this->rutin(['lila' => null])], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.pengukuran.lila']);
        $this->kirim($warga, ['pengukuran' => $this->rutin()], 'submit')->assertSessionHas('sukses');
        $this->assertEquals([JenisPemeriksaan::Rutin], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    // ---------- PUMA ----------

    public function test_puma_laki_laki_55_tahun_berisiko_bila_skor_6(): void
    {
        $warga = $this->dewasa('1971-01-01', true); // 55 tahun → skor usia 1; laki-laki → 1

        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'puma' => $this->puma('1', ['gejala_napas_pendek', 'gejala_dahak', 'gejala_batuk_tanpa_flu'])], 'submit')
            ->assertSessionHas('sukses');

        $puma = SkriningPuma::sole();
        $this->assertSame(1, $puma->skor_jenis_kelamin);
        $this->assertSame(1, $puma->skor_usia);
        $this->assertSame(6, $puma->skor_puma_total);
        $this->assertSame(StatusPpok::Berisiko, $puma->status_ppok);
        $this->assertEquals([JenisPemeriksaan::Rutin, JenisPemeriksaan::Tahunan], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    public function test_puma_skor_5_tidak_berisiko(): void
    {
        $warga = $this->dewasa('1971-01-01', true);

        $this->kirim($warga, ['puma' => $this->puma('1', ['gejala_napas_pendek', 'gejala_dahak'])])->assertSessionHas('sukses');

        $this->assertSame(5, SkriningPuma::sole()->skor_puma_total);
        $this->assertSame(StatusPpok::TidakBerisiko, SkriningPuma::sole()->status_ppok);
    }

    public function test_puma_tanpa_status_merokok_bisa_disimpan_tetapi_tidak_bisa_submit(): void
    {
        $warga = $this->dewasa();

        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'puma' => array_merge($this->puma(), ['skor_merokok' => null, 'gejala_dahak' => '1'])])
            ->assertSessionHas('sukses');
        $this->assertNull(SkriningPuma::sole()->skor_puma_total); // skor belum lengkap

        $this->kirim($warga, [], 'submit')->assertSessionHasErrors(['submit', 'bagian.puma.skor_merokok']);
    }

    public function test_puma_diabaikan_untuk_usia_di_bawah_40(): void
    {
        $warga = $this->dewasa('1990-01-01'); // 36 tahun

        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'puma' => $this->puma('2', ['gejala_dahak'])], 'submit')->assertSessionHas('sukses');

        $this->assertSame(0, SkriningPuma::count());
        $this->assertEquals([JenisPemeriksaan::Rutin], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    public function test_merokok_hanya_menerima_skor_0_sampai_2(): void
    {
        $this->kirim($this->dewasa(), ['puma' => $this->puma('3')])->assertSessionHasErrors('bagian.puma.skor_merokok');
    }

    // ---------- Peringatan & Riwayat ----------

    public function test_peringatan_bila_puma_terakhir_belum_setahun(): void
    {
        $warga = $this->dewasa();
        $lalu = $this->kegiatanBaru('2026-03-10', false);
        Pemeriksaan::create([
            'nik' => $warga->nik, 'id_kegiatan' => $lalu->id_kegiatan, 'kategori_usia_saat_periksa' => 'dewasa',
            'tanggal_pemeriksaan' => '2026-03-10', 'status_pemeriksaan' => 'selesai', 'jenis_pemeriksaan' => ['rutin', 'tahunan'],
        ]);

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertSee('data-peringatan="Pemeriksaan Tahunan terakhir untuk Sari dilakukan pada 10 Maret 2026 (belum 12 bulan). Tetap lanjutkan pengisian?"', false);
    }

    public function test_riwayat_menampilkan_ringkasan_dan_detail_dewasa(): void
    {
        $warga = $this->dewasa('1971-01-01', true);
        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'puma' => $this->puma('2', ['gejala_napas_pendek', 'gejala_dahak'])], 'submit');
        $p = Pemeriksaan::sole();

        $this->actingAs($this->kader)->get(route('riwayat.warga', $warga->nik))
            ->assertSee('Rutin Bulanan, Tahunan · IMT Normal · TD Normal · Berisiko PPOK');
        $this->actingAs($this->kader)->get(route('riwayat.show', $p))
            ->assertOk()->assertSee('Skrining PPOK PUMA')->assertSee('value="26.5"', false)
            ->assertSee('Pemeriksaan 6 Bulan Sekali<small class="tab-ket"> · tidak diperiksa</small>', false);
        $this->actingAs($this->kader)->get(route('riwayat.edit', $p))->assertOk()->assertSee('Simpan Perubahan');
    }

    // ---------- Gula Darah Sewaktu (GDS) ----------

    public function test_status_gds_dihitung_dari_angka_dengan_batas_200(): void
    {
        $this->assertNull(\App\Services\Pemeriksaan\Kalkulator::statusGulaDarah(null));
        $this->assertSame(StatusRisiko::Normal, \App\Services\Pemeriksaan\Kalkulator::statusGulaDarah('199.9'));
        $this->assertSame(StatusRisiko::Berisiko, \App\Services\Pemeriksaan\Kalkulator::statusGulaDarah('200'));

        $warga = $this->dewasa();
        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))->assertSee('Gula darah (GDS)');
        $this->kirim($warga, ['pengukuran' => $this->rutin(['kadar_gula_darah' => '200'])], 'submit')->assertSessionHas('sukses');

        $this->actingAs($this->kader)->get(route('riwayat.warga', $warga->nik))->assertSee('TD Normal · GDS Berisiko');
    }

    public function test_kotak_hasil_remaja_tanpa_gula_darah(): void
    {
        $remaja = $this->buatWarga('3404000000000009', ['tanggal_lahir' => '2010-01-01']);
        $this->kegiatan->kehadiran()->create(['nik' => $remaja->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $remaja->nik))
            ->assertSee('Hasil dihitung otomatis')->assertDontSee('Gula darah (GDS)');
    }
}
