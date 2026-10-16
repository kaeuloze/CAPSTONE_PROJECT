<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusAks;
use App\Enums\StatusPpok;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\SkriningAks;
use App\Models\SkriningPuma;
use App\Models\SkriningSkilas;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormLansia;
use App\Services\Pemeriksaan\Kalkulator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Form Pemeriksaan Lansia (Figma 430:408, 430:580, 430:683; Kartu Bantu Pemeriksaan Lansia ≥ 60 tahun).
 */
class FormLansiaTest extends TestCase
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

    private function lansia(bool $lakiLaki = false): Warga
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Mbah Sri', 'tanggal_lahir' => '1959-03-01', 'jenis_kelamin' => $lakiLaki]);
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(Warga $warga): array
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->first();
        $hasil = [];
        foreach ((new FormLansia)->bagian() as $kunci => $b) {
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

    private function rutin(): array
    {
        return ['berat_badan' => '55', 'tinggi_badan' => '150', 'lingkar_perut' => '79', 'lila' => '24',
            'td_sistolik' => '135', 'td_diastolik' => '80', 'kadar_gula_darah' => '150'];
    }

    /** Skor AKS lengkap; default semua mandiri (total 20). */
    private function aks(array $ubah = []): array
    {
        return array_merge(array_map('strval', FormLansia::SKOR_MAKS_AKS), $ubah);
    }

    private function skilas(array $ya = []): array
    {
        $kolom = ['orientasi_waktu_tempat', 'mengulang_tiga_kata', 'tes_berdiri_kursi', 'bb_berkurang', 'hilang_nafsu_makan',
            'lila_kurang', 'masalah_penglihatan', 'tes_melihat', 'tes_berbisik', 'perasaan_sedih', 'kurang_minat', 'belum_imunisasi_covid'];

        return array_combine($kolom, array_map(fn ($k) => in_array($k, $ya, true) ? '1' : '0', $kolom));
    }

    // ---------- Tampilan ----------

    public function test_form_lansia_sesuai_figma_dan_formulir_kertas(): void
    {
        $warga = $this->lansia();

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()
            ->assertSee('Pemeriksaan — Lansia (&gt;= 60 Tahun)', false)->assertSee('Lansia · 67 th')
            ->assertSeeInOrder(['Pemeriksaan Rutin Bulanan', 'Pemeriksaan 6 Bulan Sekali', 'Pemeriksaan Tahunan'])
            ->assertSee('LILA (cm)')->assertSee('Kadar gula darah (mg/dl) (Nakes)')->assertSee('Gula darah (GDS)')
            ->assertSee('Tes Penglihatan (Hitung Jari)')
            ->assertSeeInOrder(['Skrining PPOK PUMA', 'Skrining Aktivitas Kehidupan Sehari-hari (AKS)', 'Pemeriksaan Tahunan SKILAS'])
            ->assertSee('Perempuan → skor 0')->assertSee('67 tahun → skor 2')
            ->assertSee('Mengendalikan rangsang Buang Air Besar (BAB)')
            ->assertSee('Skor 3 — Mandiri')->assertSee('Skor 1 — Perlu bantuan untuk bisa duduk (2 orang)')
            ->assertSeeInOrder(['Penurunan Kognitif', 'Keterbatasan Mobilitas', 'Malnutrisi', 'Gangguan Penglihatan', 'Gangguan Pendengaran', 'Gejala Depresi'])
            ->assertSee('Belum mendapat imunisasi COVID-19')
            ->assertSee('Rujukan ke Puskesmas');
    }

    public function test_pilihan_skor_aks_mengikuti_rentang_per_item(): void
    {
        $warga = $this->lansia();
        $html = $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))->getContent();

        // Mandi 0–1 (tidak ada skor 2), Berjalan 0–3.
        $this->assertMatchesRegularExpression('/name="bagian\[aks\]\[skor_mandi\]".*?<\/select>/s', $html);
        preg_match('/name="bagian\[aks\]\[skor_mandi\]".*?<\/select>/s', $html, $mandi);
        $this->assertStringNotContainsString('value="2"', $mandi[0]);
        preg_match('/name="bagian\[aks\]\[skor_berjalan\]".*?<\/select>/s', $html, $berjalan);
        $this->assertStringContainsString('value="3"', $berjalan[0]);

        $this->kirim($warga, ['aks' => $this->aks(['skor_mandi' => '2'])])->assertSessionHasErrors('bagian.aks.skor_mandi');
    }

    // ---------- AKS ----------

    public function test_status_aks_sesuai_batas_kartu_bantu(): void
    {
        $this->assertSame(StatusAks::Mandiri, Kalkulator::statusAks(20));
        $this->assertSame(StatusAks::Ringan, Kalkulator::statusAks(19));
        $this->assertSame(StatusAks::Ringan, Kalkulator::statusAks(12));
        $this->assertSame(StatusAks::Sedang, Kalkulator::statusAks(11));
        $this->assertSame(StatusAks::Sedang, Kalkulator::statusAks(9));
        $this->assertSame(StatusAks::Berat, Kalkulator::statusAks(8));
        $this->assertSame(StatusAks::Berat, Kalkulator::statusAks(5));
        $this->assertSame(StatusAks::Total, Kalkulator::statusAks(4));
        $this->assertSame(StatusAks::Total, Kalkulator::statusAks(0));
    }

    public function test_aks_lengkap_menghitung_total_dan_status(): void
    {
        $warga = $this->lansia();

        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'aks' => $this->aks(['skor_berjalan' => '1', 'skor_naik_tangga' => '0'])], 'submit')
            ->assertSessionHas('sukses');

        $aks = SkriningAks::sole();
        $this->assertSame(16, $aks->skor_total);
        $this->assertSame(StatusAks::Ringan, $aks->status_aks);
        $this->assertEquals([JenisPemeriksaan::Rutin, JenisPemeriksaan::Tahunan], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    public function test_aks_sebagian_bisa_disimpan_tetapi_tidak_bisa_submit(): void
    {
        $warga = $this->lansia();
        $sebagian = array_fill_keys(array_keys(FormLansia::SKOR_MAKS_AKS), null);
        $sebagian['skor_bab'] = '2';
        $sebagian['skor_mandi'] = '0';

        $this->kirim($warga, ['pengukuran' => $this->rutin(), 'aks' => $sebagian])->assertSessionHas('sukses');
        $this->assertNull(SkriningAks::sole()->skor_total);

        $this->kirim($warga, [], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.aks.skor_bak', 'bagian.aks.skor_berjalan'])
            ->assertSessionDoesntHaveErrors('bagian.aks.skor_mandi'); // skor 0 tetap dihitung terisi
    }

    public function test_aks_boleh_dilewati_seluruhnya(): void
    {
        $this->kirim($this->lansia(), ['pengukuran' => $this->rutin()], 'submit')->assertSessionHas('sukses');
        $this->assertSame(0, SkriningAks::count());
    }

    // ---------- SKILAS ----------

    public function test_skilas_centang_tersimpan_dan_covid_dibalik(): void
    {
        $warga = $this->lansia();

        $this->kirim($warga, ['skilas' => $this->skilas(['mengulang_tiga_kata', 'lila_kurang', 'belum_imunisasi_covid'])])->assertSessionHas('sukses');

        $s = SkriningSkilas::sole();
        $this->assertTrue($s->mengulang_tiga_kata);
        $this->assertTrue($s->lila_kurang);
        $this->assertFalse($s->tes_berbisik);
        $this->assertFalse($s->imunisasi_covid); // "Belum imunisasi" dicentang → kolom imunisasi_covid = 0 (belum)
        $this->assertSame(['Penurunan Kognitif', 'Malnutrisi', 'Belum imunisasi COVID-19'], Kalkulator::temuanSkilas($s->getAttributes()));

        // Dibuka ulang: centang "Belum imunisasi" tetap tercentang.
        $html = $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))->getContent();
        $this->assertMatchesRegularExpression('/name="bagian\[skilas\]\[belum_imunisasi_covid\]" value="1" checked/', $html);
    }

    public function test_skilas_sudah_imunisasi_tanpa_masalah(): void
    {
        $this->kirim($this->lansia(), ['skilas' => $this->skilas(['tes_berdiri_kursi'])])->assertSessionHas('sukses');

        $s = SkriningSkilas::sole();
        $this->assertTrue($s->imunisasi_covid);
        $this->assertSame(['Keterbatasan Mobilitas'], Kalkulator::temuanSkilas($s->getAttributes()));
    }

    // ---------- PUMA & Riwayat ----------

    public function test_puma_lansia_skor_usia_2(): void
    {
        $this->kirim($this->lansia(true), ['puma' => ['skor_merokok' => '1', 'gejala_napas_pendek' => '1', 'gejala_dahak' => '1', 'gejala_batuk_tanpa_flu' => '0', 'pernah_spirometri' => '0']])
            ->assertSessionHas('sukses');

        $puma = SkriningPuma::sole();
        $this->assertSame(2, $puma->skor_usia);
        $this->assertSame(6, $puma->skor_puma_total); // 1 + 2 + 1 + 2
        $this->assertSame(StatusPpok::Berisiko, $puma->status_ppok);
    }

    public function test_riwayat_lansia_ringkasan_detail_dan_edit(): void
    {
        $warga = $this->lansia();
        $this->kirim($warga, [
            'pengukuran' => $this->rutin(),
            'aks' => $this->aks(['skor_berjalan' => '0', 'skor_naik_tangga' => '0', 'skor_transfer_kursi' => '1']),
            'skilas' => $this->skilas(['perasaan_sedih', 'tes_melihat']),
        ], 'submit')->assertSessionHas('sukses');
        $p = Pemeriksaan::sole();

        $this->actingAs($this->kader)->get(route('riwayat.warga', $warga->nik))
            ->assertSee('AKS Ketergantungan Ringan')->assertSee('SKILAS: 2 temuan')->assertSee('TD Tinggi');
        $this->actingAs($this->kader)->get(route('riwayat.show', $p))
            ->assertOk()->assertSee('Skrining Aktivitas Kehidupan Sehari-hari (AKS)')->assertSee('Pemeriksaan Tahunan SKILAS');

        // Edit: AKS dilengkapi menjadi mandiri.
        $this->actingAs($this->kader)->put(route('riwayat.update', $p), [
            'bagian' => ['aks' => $this->aks()], 'versi' => $this->versi($warga), 'diubah' => ['aks'],
        ])->assertSessionHas('sukses');
        $this->assertSame(StatusAks::Mandiri, SkriningAks::sole()->status_aks);
        $this->assertSame(20, SkriningAks::sole()->skor_total);
    }
}
