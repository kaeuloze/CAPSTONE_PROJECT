<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusImt;
use App\Enums\StatusPemeriksaan;
use App\Enums\StatusRisiko;
use App\Enums\StatusRujukan;
use App\Enums\StatusSensorik;
use App\Enums\StatusTekananDarah;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormRemaja;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Form Pemeriksaan Remaja (Figma 418:65, 424:65, 424:168; proposal Gambar 2.48–2.50).
 */
class FormRemajaTest extends TestCase
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

    private function remaja(string $nik = '3404000000000001', bool $lakiLaki = false, array $ubah = []): Warga
    {
        $warga = $this->buatWarga($nik, array_merge(['nama_warga' => 'Rina', 'tanggal_lahir' => '2010-03-01', 'jenis_kelamin' => $lakiLaki], $ubah));
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(Warga $warga): array
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->where('id_kegiatan', $this->kegiatan->id_kegiatan)->first();
        $hasil = [];
        foreach ((new FormRemaja)->bagian() as $kunci => $b) {
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

    private function rutinLengkap(array $ubah = []): array
    {
        return array_merge(['berat_badan' => '50', 'tinggi_badan' => '160', 'lingkar_perut' => '70', 'td_sistolik' => '110', 'td_diastolik' => '70'], $ubah);
    }

    // ---------- Tampilan ----------

    public function test_form_remaja_putri_sesuai_figma(): void
    {
        $warga = $this->remaja();

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()
            ->assertSee('Pemeriksaan — Remaja (5 - 17 Tahun)')->assertSee('Remaja · 16 th')
            ->assertSeeInOrder(['Rutin Bulanan', 'Screening 6-Bulan', 'Tahunan Remaja Putri'])
            ->assertSee('Skrining Penimbangan dan Pengukuran')->assertSee('Tekanan darah (mmHg)')
            ->assertSee('name="bagian[pengukuran][td_sistolik]"', false)->assertSee('name="bagian[pengukuran][td_diastolik]"', false)
            ->assertSee('Skrining Gejala TBC')->assertSee('Kontak erat Pasien TBC')
            ->assertSee('Tes Penglihatan (Hitung Jari)')->assertSee('Tes Pendengaran (Berbisik)')->assertSee('Gangguan')
            ->assertSee('Kadar HB (g/dL)')
            ->assertSee('Topik penyuluhan')->assertSee('Rujukan ke Puskesmas')
            ->assertSee('Hasil dihitung otomatis (Kalkulator IMT)');
    }

    public function test_tab_tahunan_dinonaktifkan_untuk_remaja_laki_laki(): void
    {
        $warga = $this->remaja('3404000000000002', true);

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()
            ->assertSee('disabled title="Khusus remaja putri"', false)
            ->assertDontSee('Kadar HB (g/dL)');
    }

    public function test_peringatan_bila_screening_terakhir_belum_6_bulan(): void
    {
        $warga = $this->remaja();
        $lalu = $this->kegiatanBaru('2026-07-15', false);
        Pemeriksaan::create([
            'nik' => $warga->nik, 'id_kegiatan' => $lalu->id_kegiatan, 'kategori_usia_saat_periksa' => 'remaja',
            'tanggal_pemeriksaan' => '2026-07-15', 'status_pemeriksaan' => 'selesai', 'jenis_pemeriksaan' => ['rutin', 'enam_bulanan'],
        ]);

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertSee('data-peringatan="Pemeriksaan Screening 6-Bulan terakhir untuk Rina dilakukan pada 15 Juli 2026 (belum 6 bulan). Tetap lanjutkan pengisian?"', false);
    }

    // ---------- Simpan & status otomatis ----------

    public function test_simpan_rutin_menghitung_status_otomatis(): void
    {
        $warga = $this->remaja();

        $this->kirim($warga, [
            'pengukuran' => $this->rutinLengkap(['berat_badan' => '45', 'tinggi_badan' => '160', 'lingkar_perut' => '81', 'td_sistolik' => '130', 'td_diastolik' => '80']),
            'tbc' => ['batuk_terus_menerus' => '1', 'demam_2_minggu' => '0', 'bb_tidak_stabil' => '0', 'kontak_pasien_tbc' => '0'],
        ])->assertSessionHas('sukses');

        $p = Pemeriksaan::sole();
        $ukur = $p->pengukuranDasar;
        $this->assertSame('130/80', $ukur->tekanan_darah); // 2 kotak → "120/80" (keputusan 4)
        $this->assertSame(StatusImt::Kurus, $ukur->status_imt);   // 45 / 1,6² = 17,6
        $this->assertSame(StatusTekananDarah::Tinggi, $ukur->status_tekanan_darah); // sistolik ≥ 130
        $this->assertSame(StatusRisiko::Berisiko, $ukur->status_lingkar_perut);     // perempuan > 80
        $this->assertSame(StatusRisiko::Normal, $p->skriningTbc->status_gejala_tbc); // baru 1 gejala
        $this->assertTrue($p->skriningTbc->batuk_terus_menerus);
    }

    public function test_tbc_berisiko_bila_dua_gejala(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['tbc' => ['batuk_terus_menerus' => '1', 'demam_2_minggu' => '1', 'bb_tidak_stabil' => '0', 'kontak_pasien_tbc' => '0']]);

        $this->assertSame(StatusRisiko::Berisiko, Pemeriksaan::sole()->skriningTbc->status_gejala_tbc);
    }

    public function test_submit_rutin_tanpa_gejala_tbc_tercatat_normal(): void
    {
        $warga = $this->remaja();

        // Simpan (draft) dengan semua gejala "Tidak": bagian kosong belum tercatat.
        $this->kirim($warga, ['pengukuran' => $this->rutinLengkap(), 'tbc' => ['batuk_terus_menerus' => '0', 'demam_2_minggu' => '0', 'bb_tidak_stabil' => '0', 'kontak_pasien_tbc' => '0']]);
        $this->assertNull(Pemeriksaan::sole()->skriningTbc);

        // Submit: tab Rutin dilakukan → Skrining TBC tercatat Normal (0 gejala), agar terhitung "Tidak Bergejala".
        $this->kirim($warga, [], 'submit');
        $tbc = Pemeriksaan::sole()->skriningTbc;
        $this->assertNotNull($tbc);
        $this->assertSame(StatusRisiko::Normal, $tbc->status_gejala_tbc);
        $this->assertFalse($tbc->batuk_terus_menerus);
    }

    public function test_form_dibuka_ulang_menampilkan_isian_tersimpan(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['pengukuran' => $this->rutinLengkap(['td_sistolik' => '118', 'td_diastolik' => '76']), 'penyuluhan' => ['topik_penyuluhan' => 'Gizi', 'dirujuk' => '1']]);

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertSee('value="118"', false)->assertSee('value="76"', false)->assertSee('value="Gizi"', false)
            ->assertSee('name="bagian[penyuluhan][dirujuk]" value="1" checked', false);
    }

    public function test_sensorik_normal_atau_gangguan(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['sensorik' => ['mata_kanan' => 'normal', 'mata_kiri' => 'gangguan', 'telinga_kanan' => 'normal', 'telinga_kiri' => 'normal']]);

        $s = Pemeriksaan::sole()->skriningSensorik;
        $this->assertSame(StatusSensorik::Gangguan, $s->status_penglihatan);
        $this->assertSame(StatusSensorik::Normal, $s->status_pendengaran);

        $this->kirim($warga, ['sensorik' => ['mata_kanan' => 'buram']])->assertSessionHasErrors('bagian.sensorik.mata_kanan');
    }

    public function test_hb_disimpan_untuk_putri_dan_diabaikan_untuk_putra(): void
    {
        $putri = $this->remaja();
        $this->kirim($putri, ['hb' => ['kadar_hb' => '11.5']]);
        $this->assertSame('11.5', Pemeriksaan::where('nik', $putri->nik)->sole()->pengukuranDasar->kadar_hb);

        $putra = $this->remaja('3404000000000002', true);
        $this->kirim($putra, ['hb' => ['kadar_hb' => '13']]);
        $this->assertNull(Pemeriksaan::where('nik', $putra->nik)->first()?->pengukuranDasar);
    }

    public function test_rujukan_dan_penyuluhan(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['penyuluhan' => ['topik_penyuluhan' => 'Anemia remaja', 'dirujuk' => '1']]);

        $r = Pemeriksaan::sole()->penyuluhanRujukan;
        $this->assertSame('Anemia remaja', $r->topik_penyuluhan);
        $this->assertSame(StatusRujukan::RujukPuskesmas, $r->status_rujukan);
        $this->assertSame('2026-10-16', $r->tanggal_rujukan->toDateString());
    }

    public function test_validasi_tekanan_darah_harus_berpasangan(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['pengukuran' => ['td_sistolik' => '120', 'td_diastolik' => '']])
            ->assertSessionHasErrors('bagian.pengukuran.td_diastolik');
        $this->kirim($warga, ['pengukuran' => ['berat_badan' => '0']])->assertSessionHasErrors('bagian.pengukuran.berat_badan');
        $this->assertSame(0, Pemeriksaan::count());
    }

    // ---------- Submit ----------

    public function test_submit_butuh_semua_isian_wajib_rutin(): void
    {
        $warga = $this->remaja();
        $this->kirim($warga, ['pengukuran' => $this->rutinLengkap(['lingkar_perut' => ''])], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.pengukuran.lingkar_perut']);

        $this->assertSame(0, Pemeriksaan::count());
    }

    public function test_submit_lengkap_mencatat_jenis_pemeriksaan(): void
    {
        $warga = $this->remaja();

        $this->kirim($warga, [
            'pengukuran' => $this->rutinLengkap(),
            'sensorik' => ['mata_kanan' => 'normal', 'mata_kiri' => 'normal', 'telinga_kanan' => '', 'telinga_kiri' => ''],
            'hb' => ['kadar_hb' => '12.5'],
        ], 'submit')->assertSessionHas('sukses', 'Pemeriksaan Rina berhasil di-Submit dan masuk ke Riwayat Pemeriksaan.');

        $p = Pemeriksaan::sole();
        $this->assertSame(StatusPemeriksaan::Selesai, $p->status_pemeriksaan);
        $this->assertEqualsCanonicalizing([JenisPemeriksaan::Rutin, JenisPemeriksaan::EnamBulanan, JenisPemeriksaan::Tahunan], $p->jenis_pemeriksaan);
        $this->assertSame(StatusImt::Normal, $p->pengukuranDasar->status_imt); // 50 / 1,6² = 19,5
    }

    public function test_dua_meja_rutin_dan_hb_pada_tabel_yang_sama_tidak_saling_menimpa(): void
    {
        $warga = $this->remaja();
        $versiAwal = $this->versi($warga);

        // Meja timbang
        $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => ['pengukuran' => $this->rutinLengkap(), 'hb' => ['kadar_hb' => '']], 'versi' => $versiAwal, 'diubah' => ['pengukuran'], 'aksi' => 'simpan',
        ])->assertSessionHas('sukses');
        // Meja lab (layar lama, isian penimbangan masih kosong)
        $this->actingAs($this->buatKader('kader_lab'))->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => ['pengukuran' => ['berat_badan' => ''], 'hb' => ['kadar_hb' => '13.1']], 'versi' => $versiAwal, 'diubah' => ['hb'], 'aksi' => 'simpan',
        ])->assertSessionHas('sukses');

        $ukur = Pemeriksaan::sole()->pengukuranDasar;
        $this->assertSame('50.00', $ukur->berat_badan);
        $this->assertSame('13.1', $ukur->kadar_hb);
    }

    // ---------- Kalkulator ----------

    public function test_batas_kalkulator_sesuai_format_rekap(): void
    {
        $this->assertSame(StatusImt::Kurus, Kalkulator::statusImt(18.4));
        $this->assertSame(StatusImt::Normal, Kalkulator::statusImt(18.5));
        $this->assertSame(StatusImt::Normal, Kalkulator::statusImt(24.9));
        $this->assertSame(StatusImt::Gemuk, Kalkulator::statusImt(25.0));
        $this->assertSame(StatusImt::Obesitas, Kalkulator::statusImt(27.1));
        $this->assertSame(StatusTekananDarah::Normal, Kalkulator::statusTekananDarah('129', '84'));
        $this->assertSame(StatusTekananDarah::Tinggi, Kalkulator::statusTekananDarah('120', '85'));
        $this->assertSame(StatusRisiko::Normal, Kalkulator::statusLingkarPerut('80', false));
        $this->assertSame(StatusRisiko::Berisiko, Kalkulator::statusLingkarPerut('80.5', false));
        $this->assertSame(StatusRisiko::Normal, Kalkulator::statusLingkarPerut('90', true));
        $this->assertSame(19.5, Kalkulator::imt('50', '160'));
    }
}
