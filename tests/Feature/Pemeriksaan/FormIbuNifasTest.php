<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisIbu;
use App\Enums\StatusImt;
use App\Enums\StatusTekananDarah;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanBumil;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormIbuNifas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Form Pemeriksaan Ibu Nifas / Menyusui (Figma 430:1099; Kartu Bantu Ibu Nifas/Menyusui).
 */
class FormIbuNifasTest extends TestCase
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

    private function ibu(string $kondisi = 'nifas'): Warga
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Rani', 'tanggal_lahir' => '1996-05-01', 'status_kondisi' => $kondisi]);
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function kirim(Warga $warga, array $isian, string $aksi = 'simpan')
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->first();
        $versi = [];
        foreach ((new FormIbuNifas)->bagian() as $kunci => $b) {
            $versi[$kunci] = $b->versi($p?->{$b->relasi}()->first());
        }

        return $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => $isian, 'versi' => $versi, 'diubah' => array_keys($isian), 'aksi' => $aksi,
        ]);
    }

    private function wajib(array $ukur = []): array
    {
        return [
            'waktu' => ['usia_kehamilan_minggu' => '7-28 hari'],
            'pengukuran' => array_merge(['berat_badan' => '55', 'td_sistolik' => '115', 'td_diastolik' => '75'], $ukur),
        ];
    }

    public function test_form_ibu_nifas_sesuai_figma_dan_kartu_kertas(): void
    {
        $warga = $this->ibu();

        $respon = $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik));
        // Tab Ibu Nifas langsung terbuka (tab Ibu Hamil dikunci).
        $this->assertMatchesRegularExpression('/aria-selected="true"\s+class="aktif"\s+data-tab="nifas"/', $respon->getContent());
        $respon->assertOk()
            ->assertSee('Pemeriksaan — Ibu Hamil / Nifas')->assertSee('Ibu Nifas · 30 th')
            ->assertSee('disabled title="Status ibu saat ini: Ibu Nifas"', false)
            ->assertSee('Waktu ke Posyandu Setelah Bersalin')->assertSee('&lt; 7 hari', false)->assertSee('Bulan 24')
            ->assertSee('Berat badan (kg)')->assertSee('Tekanan darah (mmHg)')
            ->assertDontSee('LILA (cm)')->assertDontSee('Tinggi Badan (cm)')
            ->assertSee('Ploting IMT sesuai kurva Buku KIA')
            ->assertSee('Demam ≥ 2 minggu')
            ->assertSee('Jika nakes memberikan vitamin A, tuliskan jumlah kapsul')->assertSee('name="bagian[pelayanan][jumlah_kapsul_vit_a]"', false)
            ->assertSee('Rutin konsumsi vitamin A')->assertSee('Menyusui')->assertSee('KB pasca persalinan')
            ->assertSee('mis. perawatan masa nifas / ASI eksklusif')
            ->assertDontSee('Mengikuti kelas ibu hamil')->assertDontSee('Pemeriksaan Sesuai Umur Kehamilan');
    }

    public function test_ibu_menyusui_memakai_form_nifas(): void
    {
        $warga = $this->ibu('menyusui');

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()->assertSee('disabled title="Status ibu saat ini: Ibu Menyusui"', false)->assertSee('Waktu ke Posyandu Setelah Bersalin');
    }

    public function test_simpan_lengkap_mencatat_jenis_ibu_nifas_dan_ploting(): void
    {
        $warga = $this->ibu();

        $this->kirim($warga, $this->wajib(['td_sistolik' => '135']) + [
            'ploting_imt' => ['status_ploting_imt' => 'normal'],
            'pelayanan' => ['jumlah_kapsul_vit_a' => '2', 'status_rutin_vit_a' => '1', 'status_menyusui' => '1', 'status_kb_pascasalin' => '0'],
        ], 'submit')->assertSessionHas('sukses');

        $bumil = PemeriksaanBumil::sole();
        $this->assertSame(JenisIbu::Nifas, $bumil->jenis_ibu);
        $this->assertSame('7-28 hari', $bumil->usia_kehamilan_minggu);
        $this->assertSame(StatusTekananDarah::Tinggi, $bumil->status_ploting_tekanan_darah);
        $this->assertNull($bumil->status_ploting_lila);
        $this->assertSame(StatusImt::Normal, $bumil->status_ploting_imt);
        $this->assertSame(2, $bumil->jumlah_kapsul_vit_a);
        $this->assertTrue($bumil->status_rutin_vit_a);
        $this->assertTrue($bumil->status_menyusui);
        $this->assertFalse($bumil->status_kb_pascasalin);
        $this->assertSame('ibu_nifas', Pemeriksaan::sole()->getRawOriginal('kategori_usia_saat_periksa'));
    }

    public function test_submit_butuh_waktu_bb_dan_td(): void
    {
        $this->kirim($this->ibu(), ['pengukuran' => ['berat_badan' => null, 'td_sistolik' => '115', 'td_diastolik' => '75']], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.waktu.usia_kehamilan_minggu', 'bagian.pengukuran.berat_badan']);
    }

    public function test_validasi_waktu_dan_kapsul(): void
    {
        $this->kirim($this->ibu(), ['waktu' => ['usia_kehamilan_minggu' => 'Bulan 25'], 'pelayanan' => ['jumlah_kapsul_vit_a' => '11']])
            ->assertSessionHasErrors(['bagian.waktu.usia_kehamilan_minggu', 'bagian.pelayanan.jumlah_kapsul_vit_a']);
    }

    public function test_riwayat_ibu_nifas(): void
    {
        $warga = $this->ibu();
        $this->kirim($warga, $this->wajib(), 'submit')->assertSessionHas('sukses');

        $this->actingAs($this->kader)->get(route('riwayat.warga', $warga->nik))
            ->assertSee('Ibu Nifas')->assertSee('Rutin Bulanan · TD Normal · Setelah bersalin 7-28 hari');
        $this->actingAs($this->kader)->get(route('riwayat.show', Pemeriksaan::sole()))
            ->assertOk()->assertSee('Waktu ke Posyandu Setelah Bersalin');
    }
}
