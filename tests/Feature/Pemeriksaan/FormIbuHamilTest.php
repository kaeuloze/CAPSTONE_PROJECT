<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisIbu;
use App\Enums\JenisPemeriksaan;
use App\Enums\StatusImt;
use App\Enums\StatusLila;
use App\Enums\StatusTekananDarah;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanBumil;
use App\Models\PengukuranDasar;
use App\Models\SkriningTbc;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormIbuHamil;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Form Pemeriksaan Ibu Hamil (Figma 430:927; Kartu Bantu Pemeriksaan Ibu Hamil/Nifas/Menyusui).
 */
class FormIbuHamilTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-16 08:00:00'));
        $this->kader = $this->buatKader();
        $this->kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Oktober', 'tanggal_kegiatan' => '2026-10-16',
            'status_kegiatan' => true, 'waktu_dibuka' => now(),
        ]);
    }

    private function bumil(): Warga
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Dewi', 'tanggal_lahir' => '1998-02-01', 'status_kondisi' => 'hamil']);
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(Warga $warga): array
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->first();
        $hasil = [];
        foreach ((new FormIbuHamil)->bagian() as $kunci => $b) {
            $hasil[$kunci] = $b->versi($p?->{$b->relasi}()->first());
        }

        return $hasil;
    }

    private function kirim(Warga $warga, array $isian, string $aksi = 'simpan', ?array $versi = null)
    {
        return $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => $isian, 'versi' => $versi ?? $this->versi($warga), 'diubah' => array_keys($isian), 'aksi' => $aksi,
        ]);
    }

    private function wajib(array $ukur = []): array
    {
        return [
            'kehamilan' => ['usia_kehamilan_minggu' => '20-24'],
            'pengukuran' => array_merge(['berat_badan' => '58', 'lila' => '24', 'td_sistolik' => '110', 'td_diastolik' => '70'], $ukur),
        ];
    }

    // ---------- Tampilan ----------

    public function test_form_ibu_hamil_sesuai_figma_dan_kartu_kertas(): void
    {
        $warga = $this->bumil();

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()
            ->assertSee('Pemeriksaan — Ibu Hamil / Nifas')->assertSee('Ibu Hamil · 28 th')
            ->assertSeeInOrder(['Ibu Hamil', 'Ibu Nifas'])
            ->assertSee('disabled title="Status ibu saat ini: Ibu Hamil"', false)
            ->assertSee('Pemeriksaan Sesuai Umur Kehamilan')->assertSee('&lt; 4 minggu', false)->assertSee('36 - 40 minggu')
            ->assertSeeInOrder(['Berat badan (kg)', 'LILA (cm)', 'Tekanan darah (mmHg)'])
            ->assertDontSee('Tinggi Badan (cm)')
            ->assertSee('Ploting IMT sesuai kurva Buku KIA')->assertSee('Risiko (IMT sebelum hamil &gt; 25 kg/m²)', false)
            ->assertSee('Demam ≥ 2 minggu') // 4 gejala TBC sesuai kertas
            ->assertSee('Jumlah TTD/MMS yang diberikan nakes (butir)')->assertSee('name="bagian[pelayanan][jumlah_ttd_mms]"', false)
            ->assertSee('name="bagian[pelayanan][komposisi_mt_bumil_kek]"', false)
            ->assertSee('Mengikuti kelas ibu hamil')->assertSee('Rujukan ke Puskesmas')
            ->assertSee('Ploting LILA')
            ->assertSee('Simpan dan Submit berlaku untuk tab sesuai status ibu saat ini');
    }

    // ---------- Simpan & ploting otomatis ----------

    public function test_ploting_lila_dan_td_dihitung_otomatis(): void
    {
        $this->assertSame(StatusLila::Kek, Kalkulator::statusLila('23.4'));
        $this->assertSame(StatusLila::Normal, Kalkulator::statusLila('23.5'));

        $warga = $this->bumil();
        $this->kirim($warga, $this->wajib(['lila' => '22', 'td_sistolik' => '130', 'td_diastolik' => '80']))->assertSessionHas('sukses');

        $bumil = PemeriksaanBumil::sole();
        $this->assertSame(JenisIbu::Hamil, $bumil->jenis_ibu);
        $this->assertSame('20-24', $bumil->usia_kehamilan_minggu);
        $this->assertSame(StatusLila::Kek, $bumil->status_ploting_lila);
        $this->assertSame(StatusTekananDarah::Tinggi, $bumil->status_ploting_tekanan_darah);

        $ukur = PengukuranDasar::sole();
        $this->assertSame('130/80', $ukur->tekanan_darah);
        $this->assertSame('22.0', $ukur->getRawOriginal('lila'));
        $this->assertNull($ukur->tinggi_badan);
        $this->assertNull($ukur->status_imt);
    }

    public function test_ploting_imt_pelayanan_dan_kelas_tersimpan(): void
    {
        $warga = $this->bumil();

        $this->kirim($warga, $this->wajib() + [
            'ploting_imt' => ['status_ploting_imt' => 'gemuk'],
            'pelayanan' => ['jumlah_ttd_mms' => '30', 'status_konsumsi_ttd_rutin' => '1', 'komposisi_mt_bumil_kek' => 'Biskuit MT 2 keping/hari', 'status_rutin_mt_kek' => '0'],
            'kelas' => ['ikut_kelas_ibu_hamil' => '1'],
            'tbc' => ['batuk_terus_menerus' => '1', 'demam_2_minggu' => '1', 'bb_tidak_stabil' => '0', 'kontak_pasien_tbc' => '0'],
        ], 'submit')->assertSessionHas('sukses');

        $bumil = PemeriksaanBumil::sole(); // semua bagian menulis ke satu baris yang sama
        $this->assertSame(StatusImt::Gemuk, $bumil->status_ploting_imt);
        $this->assertSame(30, $bumil->jumlah_ttd_mms);
        $this->assertTrue($bumil->status_konsumsi_ttd_rutin);
        $this->assertSame('Biskuit MT 2 keping/hari', $bumil->komposisi_mt_bumil_kek);
        $this->assertFalse($bumil->status_rutin_mt_kek);
        $this->assertTrue($bumil->ikut_kelas_ibu_hamil);
        $this->assertSame(StatusLila::Normal, $bumil->status_ploting_lila);
        $this->assertSame(\App\Enums\StatusRisiko::Berisiko, SkriningTbc::sole()->status_gejala_tbc);
        $this->assertEquals([JenisPemeriksaan::Rutin], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    public function test_submit_butuh_usia_kehamilan_bb_lila_td(): void
    {
        $warga = $this->bumil();

        $this->kirim($warga, ['pengukuran' => ['berat_badan' => '58', 'lila' => null, 'td_sistolik' => '110', 'td_diastolik' => '70']], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.kehamilan.usia_kehamilan_minggu', 'bagian.pengukuran.lila']);
    }

    public function test_validasi_pilihan_dan_teks(): void
    {
        $this->kirim($this->bumil(), [
            'kehamilan' => ['usia_kehamilan_minggu' => '40-44'],
            'ploting_imt' => ['status_ploting_imt' => 'obesitas'],
            'pelayanan' => ['jumlah_ttd_mms' => '-1', 'komposisi_mt_bumil_kek' => '<script>'],
        ])->assertSessionHasErrors(['bagian.kehamilan.usia_kehamilan_minggu', 'bagian.ploting_imt.status_ploting_imt',
            'bagian.pelayanan.jumlah_ttd_mms', 'bagian.pelayanan.komposisi_mt_bumil_kek']);
    }

    public function test_dua_meja_mengisi_tabel_bumil_yang_sama_tidak_saling_menimpa(): void
    {
        $warga = $this->bumil();
        $versiAwal = $this->versi($warga);

        // Meja penimbangan & meja pelayanan membuka form bersamaan.
        $this->kirim($warga, $this->wajib(['lila' => '22']), 'simpan', $versiAwal)->assertSessionHas('sukses');
        $this->kirim($warga, ['pelayanan' => ['jumlah_ttd_mms' => '30', 'status_konsumsi_ttd_rutin' => '1', 'komposisi_mt_bumil_kek' => null, 'status_rutin_mt_kek' => '0']], 'simpan', $versiAwal)
            ->assertSessionHas('sukses');

        $bumil = PemeriksaanBumil::sole();
        $this->assertSame('20-24', $bumil->usia_kehamilan_minggu);
        $this->assertSame(StatusLila::Kek, $bumil->status_ploting_lila);
        $this->assertSame(30, $bumil->jumlah_ttd_mms);
    }

    // ---------- Riwayat ----------

    public function test_riwayat_ibu_hamil(): void
    {
        $warga = $this->bumil();
        $this->kirim($warga, $this->wajib(['lila' => '22']), 'submit')->assertSessionHas('sukses');
        $p = Pemeriksaan::sole();

        $this->actingAs($this->kader)->get(route('riwayat.warga', $warga->nik))
            ->assertSee('Ibu Hamil')->assertSee('Rutin Bulanan · TD Normal · Usia kehamilan 20–24 minggu · LILA KEK');
        $this->actingAs($this->kader)->get(route('riwayat.show', $p))->assertOk()->assertSee('Pemeriksaan Sesuai Umur Kehamilan');

        // Edit: LILA dikoreksi → ploting ikut berubah.
        $this->actingAs($this->kader)->put(route('riwayat.update', $p), [
            'bagian' => ['pengukuran' => ['berat_badan' => '58', 'lila' => '25', 'td_sistolik' => '110', 'td_diastolik' => '70']],
            'versi' => $this->versi($warga), 'diubah' => ['pengukuran'],
        ])->assertSessionHas('sukses');
        $this->assertSame(StatusLila::Normal, PemeriksaanBumil::sole()->status_ploting_lila);
    }
}
