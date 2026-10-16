<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusImt;
use App\Enums\StatusPemeriksaan;
use App\Enums\StatusTekananDarah;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\PengukuranDasar;
use App\Models\Warga;
use App\Services\Pemeriksaan\Form\FormRemaja;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Riwayat Pemeriksaan (Figma 198:723, 141:921, 141:3062, 253:1732).
 */
class RiwayatPemeriksaanTest extends TestCase
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

    private function remaja(string $nik = '3404000000000001', bool $lakiLaki = false, string $nama = 'Rina'): Warga
    {
        $warga = $this->buatWarga($nik, ['nama_warga' => $nama, 'tanggal_lahir' => '2010-03-01', 'jenis_kelamin' => $lakiLaki]);
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(?Pemeriksaan $p): array
    {
        $hasil = [];
        foreach ((new FormRemaja)->bagian() as $kunci => $b) {
            $hasil[$kunci] = $b->versi($p?->{$b->relasi}()->first());
        }

        return $hasil;
    }

    private function rutin(array $ubah = []): array
    {
        return array_merge(['berat_badan' => '50', 'tinggi_badan' => '160', 'lingkar_perut' => '70', 'td_sistolik' => '110', 'td_diastolik' => '70'], $ubah);
    }

    /** Pemeriksaan yang sudah di-Submit lewat menu Pemeriksaan. */
    private function submit(Warga $warga, array $isian = []): Pemeriksaan
    {
        $isian = $isian ?: ['pengukuran' => $this->rutin(), 'penyuluhan' => ['topik_penyuluhan' => 'Gizi seimbang', 'dirujuk' => '0']];
        $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), [
            'bagian' => $isian, 'versi' => $this->versi(null), 'diubah' => array_keys($isian), 'aksi' => 'submit',
        ])->assertSessionHas('sukses');

        return Pemeriksaan::where('nik', $warga->nik)->sole();
    }

    private function ubah(Pemeriksaan $p, array $isian, ?array $versi = null)
    {
        return $this->actingAs($this->kader)->put(route('riwayat.update', $p), [
            'bagian' => $isian, 'versi' => $versi ?? $this->versi($p), 'diubah' => array_keys($isian),
        ]);
    }

    // ---------- Daftar Warga Riwayat ----------

    public function test_daftar_berisi_seluruh_warga_dengan_kunjungan_terakhir(): void
    {
        $rina = $this->remaja();
        $this->submit($rina);
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi Belum Periksa']);
        $this->buatWarga('3404000000000003', ['nama_warga' => 'Balita Kecil', 'tanggal_lahir' => '2024-01-01']);

        $this->actingAs($this->kader)->get(route('riwayat.index'))
            ->assertOk()
            ->assertSee('Daftar Warga Riwayat Pemeriksaan')
            ->assertSeeInOrder(['Balita Kecil', '-', 'Budi Belum Periksa', '-', 'Rina', '16/10/2026'])
            ->assertSee('Lihat Riwayat');
    }

    public function test_draft_tidak_dihitung_sebagai_kunjungan(): void
    {
        $rina = $this->remaja();
        $this->buatPemeriksaan($rina, $this->kegiatan, ['kategori_usia_saat_periksa' => 'remaja', 'status_pemeriksaan' => 'draft']);

        $this->actingAs($this->kader)->get(route('riwayat.index'))->assertDontSee('16/10/2026');
        $this->actingAs($this->kader)->get(route('riwayat.warga', $rina->nik))
            ->assertSee('Belum ada pemeriksaan yang di-Submit untuk warga ini.');
    }

    public function test_cari_dan_filter_kategori(): void
    {
        $this->remaja();
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi Dewasa']);

        $this->actingAs($this->kader)->get(route('riwayat.index', ['kategori' => 'remaja']))
            ->assertSee('Rina')->assertDontSee('Budi Dewasa');
        $this->actingAs($this->kader)->get(route('riwayat.index', ['cari' => 'budi']))
            ->assertSee('Budi Dewasa')->assertDontSee('Rina');
        $this->actingAs($this->kader)->get(route('riwayat.index', ['kategori' => 'bukan']))
            ->assertSessionHasErrors('kategori');
    }

    // ---------- Riwayat per warga ----------

    public function test_riwayat_per_warga_menampilkan_ringkasan_jenis_dan_hasil(): void
    {
        $rina = $this->remaja();
        $this->submit($rina, [
            'pengukuran' => $this->rutin(['td_sistolik' => '135']),
            'tbc' => ['batuk_terus_menerus' => '1', 'demam_2_minggu' => '1', 'bb_tidak_stabil' => '0', 'kontak_pasien_tbc' => '0'],
            'sensorik' => ['mata_kanan' => 'normal', 'mata_kiri' => 'normal', 'telinga_kanan' => 'normal', 'telinga_kiri' => 'normal'],
            'penyuluhan' => ['topik_penyuluhan' => null, 'dirujuk' => '1'],
        ]);

        $this->actingAs($this->kader)->get(route('riwayat.warga', $rina->nik))
            ->assertOk()
            ->assertSee('Kelola Riwayat Pemeriksaan')
            ->assertSee('16 Oktober 2026')->assertSee('Posyandu Oktober')
            ->assertSee('Rutin Bulanan, 6 Bulan Sekali · IMT Normal · TD Tinggi · Gejala TBC Berisiko · Dirujuk ke Puskesmas')
            ->assertSee('Detail');
    }

    // ---------- Detail ----------

    public function test_detail_hanya_baca_dengan_tombol_edit_dan_hapus(): void
    {
        $p = $this->submit($this->remaja());

        $this->actingAs($this->kader)->get(route('riwayat.show', $p))
            ->assertOk()
            ->assertSee('Detail Pemeriksaan — 16 Oktober 2026')
            ->assertSee('<fieldset class="bagian-baca" disabled>', false)
            ->assertSee('value="50"', false)->assertSee('value="110"', false)
            ->assertSee('Gizi seimbang')
            ->assertSee('tidak diperiksa')
            ->assertSee('Pemeriksaan Screening 6-Bulan tidak dilakukan pada kunjungan ini.')
            ->assertSee(route('riwayat.edit', $p))
            ->assertSee('Hapus data pemeriksaan ini?')
            ->assertDontSee('name="versi[pengukuran]"', false);
    }

    public function test_draft_tidak_bisa_dibuka_di_riwayat(): void
    {
        $rina = $this->remaja();
        $draft = $this->buatPemeriksaan($rina, $this->kegiatan, ['kategori_usia_saat_periksa' => 'remaja', 'status_pemeriksaan' => 'draft']);

        $this->actingAs($this->kader)->get(route('riwayat.show', $draft))->assertNotFound();
        $this->actingAs($this->kader)->get(route('riwayat.edit', $draft))->assertNotFound();
        $this->actingAs($this->kader)->delete(route('riwayat.destroy', $draft))->assertNotFound();
        $this->assertModelExists($draft);
    }

    public function test_riwayat_wajib_login(): void
    {
        $this->get(route('riwayat.index'))->assertRedirect(route('login'));
    }

    // ---------- Edit ----------

    public function test_edit_mengubah_nilai_dan_menghitung_ulang_status(): void
    {
        $p = $this->submit($this->remaja());

        $this->actingAs($this->kader)->get(route('riwayat.edit', $p))
            ->assertOk()->assertSee('Edit Pemeriksaan — 16 Oktober 2026')->assertSee('Simpan Perubahan')
            ->assertSee('value="50"', false);

        $this->ubah($p, ['pengukuran' => $this->rutin(['berat_badan' => '80', 'td_sistolik' => '140', 'td_diastolik' => '90'])])
            ->assertRedirect(route('riwayat.show', $p))
            ->assertSessionHas('sukses', 'Perubahan data pemeriksaan berhasil disimpan.');

        $ukur = $p->pengukuranDasar()->first();
        $this->assertSame('140/90', $ukur->tekanan_darah);
        $this->assertSame(StatusImt::Obesitas, $ukur->status_imt); // 80 / 1,6² = 31,3
        $this->assertSame(StatusTekananDarah::Tinggi, $ukur->status_tekanan_darah);
        $this->assertSame(StatusPemeriksaan::Selesai, $p->refresh()->status_pemeriksaan);
    }

    public function test_edit_menambah_screening_mengubah_jenis_pemeriksaan(): void
    {
        $p = $this->submit($this->remaja());

        $this->ubah($p, ['sensorik' => ['mata_kanan' => 'gangguan', 'mata_kiri' => 'normal', 'telinga_kanan' => 'normal', 'telinga_kiri' => 'normal']])
            ->assertSessionHas('sukses');
        $this->assertEquals([JenisPemeriksaan::Rutin, JenisPemeriksaan::EnamBulanan], $p->refresh()->jenis_pemeriksaan);

        // Screening dikosongkan lagi → jenis kembali Rutin saja.
        $this->ubah($p, ['sensorik' => ['mata_kanan' => null, 'mata_kiri' => null, 'telinga_kanan' => null, 'telinga_kiri' => null]])
            ->assertSessionHas('sukses');
        $this->assertEquals([JenisPemeriksaan::Rutin], $p->refresh()->jenis_pemeriksaan);
    }

    public function test_edit_tetap_mewajibkan_isian_bertanda_bintang(): void
    {
        $p = $this->submit($this->remaja());

        $this->ubah($p, ['pengukuran' => $this->rutin(['berat_badan' => null])])
            ->assertSessionHasErrors(['submit', 'bagian.pengukuran.berat_badan']);

        $this->assertSame('50.00', PengukuranDasar::sole()->getRawOriginal('berat_badan')); // transaksi dibatalkan
    }

    public function test_edit_ditolak_bila_bagian_yang_sama_baru_diubah_kader_lain(): void
    {
        $p = $this->submit($this->remaja());
        $versiLama = $this->versi($p);

        $this->ubah($p, ['pengukuran' => $this->rutin(['berat_badan' => '52'])])->assertSessionHas('sukses'); // meja A
        $this->ubah($p, ['pengukuran' => $this->rutin(['berat_badan' => '55'])], $versiLama)                  // meja B (data lama)
            ->assertSessionHas('galat', fn ($pesan) => str_contains($pesan, 'Skrining Penimbangan dan Pengukuran'));
        $this->assertSame('52.00', PengukuranDasar::sole()->getRawOriginal('berat_badan'));

        // Bagian lain dari meja B tetap bisa disimpan.
        $this->ubah($p, ['penyuluhan' => ['topik_penyuluhan' => 'Anemia', 'dirujuk' => '0']], $versiLama)->assertSessionHas('sukses');
    }

    public function test_edit_tidak_menyimpan_hb_remaja_laki_laki(): void
    {
        $p = $this->submit($this->remaja('3404000000000002', true, 'Andi'));

        $this->actingAs($this->kader)->get(route('riwayat.edit', $p))->assertSee('disabled title="Khusus remaja putri"', false);
        $this->ubah($p, ['hb' => ['kadar_hb' => '11']])->assertSessionHas('sukses');
        $this->assertNull(PengukuranDasar::sole()->kadar_hb);
    }

    public function test_edit_bisa_dilakukan_setelah_kegiatan_selesai(): void
    {
        $p = $this->submit($this->remaja());
        $this->kegiatan->update(['status_kegiatan' => false]);

        $this->ubah($p, ['pengukuran' => $this->rutin(['berat_badan' => '51'])])->assertSessionHas('sukses');
        $this->assertSame('51.00', PengukuranDasar::sole()->getRawOriginal('berat_badan'));
    }

    // ---------- Hapus ----------

    public function test_hapus_menghapus_detail_dan_warga_kembali_ke_antrean_bila_kegiatan_berlangsung(): void
    {
        $rina = $this->remaja();
        $p = $this->submit($rina);
        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))->assertDontSee(route('pemeriksaan.form', $rina->nik));

        $this->actingAs($this->kader)->delete(route('riwayat.destroy', $p))
            ->assertRedirect(route('riwayat.warga', $rina->nik))
            ->assertSessionHas('sukses', fn ($pesan) => str_contains($pesan, 'muncul kembali di menu Pemeriksaan'));

        $this->assertModelMissing($p);
        $this->assertSame(0, PengukuranDasar::count()); // detail ikut terhapus (CASCADE)
        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))->assertSee(route('pemeriksaan.form', $rina->nik));
    }

    public function test_hapus_pada_kegiatan_selesai_tanpa_keterangan_antrean(): void
    {
        $rina = $this->remaja();
        $p = $this->submit($rina);
        $this->kegiatan->update(['status_kegiatan' => false]);

        $this->actingAs($this->kader)->delete(route('riwayat.destroy', $p))
            ->assertSessionHas('sukses', fn ($pesan) => ! str_contains($pesan, 'muncul kembali'));
        $this->assertModelMissing($p);
    }

    public function test_edit_pemeriksaan_yang_sudah_dihapus_kader_lain(): void
    {
        $rina = $this->remaja();
        $p = $this->submit($rina);
        $versi = $this->versi($p);
        $p->delete(); // meja lain menghapus

        $this->ubah($p, ['pengukuran' => $this->rutin()], $versi)->assertNotFound();
    }
}
