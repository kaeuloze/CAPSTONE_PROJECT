<?php

namespace Tests\Feature\Kegiatan;

use App\Models\Kader;
use App\Models\Kegiatan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Halaman Presensi (wireframe 2.42; Activity Diagram 2.7): tandai hadir / batalkan tiap klik,
 * ringkasan, pencarian & filter, polling realtime antar-meja, kegiatan selesai = hanya-lihat.
 */
class PresensiTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00'));
        $this->kader = $this->buatKader();
        $this->kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Oktober 2026', 'tanggal_kegiatan' => '2026-10-10',
            'status_kegiatan' => true, 'waktu_dibuka' => now(),
        ]);
    }

    private function atur(string $nik, string $hadir, ?Kader $kader = null)
    {
        return $this->actingAs($kader ?? $this->kader)
            ->postJson(route('presensi.atur', [$this->kegiatan, $nik]), ['hadir' => $hadir]);
    }

    public function test_halaman_presensi_menampilkan_semua_warga_dan_ringkasan(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Balita', 'tanggal_lahir' => '2023-01-01']);
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Siti Dewasa']);

        $this->actingAs($this->kader)->get(route('presensi.index', $this->kegiatan))
            ->assertOk()->assertSee('Posyandu Oktober 2026')->assertSee('Status: <span id="label-status">Berlangsung</span>', false)
            ->assertSee('Budi Balita')->assertSee('Siti Dewasa')->assertSee('Balita')
            ->assertSee('id="jumlah-total">2<', false)->assertSee('id="jumlah-hadir">0<', false)
            ->assertSee('Selesaikan Kegiatan')->assertSee('Tandai Hadir');
    }

    public function test_tandai_hadir_langsung_tersimpan_dan_bisa_dibatalkan(): void
    {
        $warga = $this->buatWarga('3404000000000001');

        $this->atur($warga->nik, '1')->assertOk()->assertJson(['nik' => $warga->nik, 'hadir' => true]);
        $kehadiran = $this->kegiatan->kehadiran()->first();
        $this->assertTrue($kehadiran->status_kehadiran);
        $this->assertSame('2026-10-10 08:00:00', $kehadiran->waktu_kehadiran->toDateTimeString());

        $this->atur($warga->nik, '0')->assertOk()->assertJson(['hadir' => false]);
        $this->assertFalse($kehadiran->fresh()->status_kehadiran);
        $this->assertNull($kehadiran->fresh()->waktu_kehadiran);
        $this->assertSame(1, $this->kegiatan->kehadiran()->count()); // tidak ada baris ganda
    }

    public function test_dua_kader_menandai_warga_yang_sama_hasilnya_tetap_hadir(): void
    {
        $warga = $this->buatWarga('3404000000000001');
        $kader2 = $this->buatKader('kader_meja_2');

        $this->atur($warga->nik, '1')->assertOk();
        $this->travel(3)->minutes();
        $this->atur($warga->nik, '1', $kader2)->assertOk();

        $kehadiran = $this->kegiatan->kehadiran()->sole();
        $this->assertTrue($kehadiran->status_kehadiran);
        $this->assertSame('2026-10-10 08:00:00', $kehadiran->waktu_kehadiran->toDateTimeString()); // jam pertama dipertahankan
    }

    public function test_tanpa_javascript_formulir_biasa_tetap_berfungsi(): void
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi']);

        $this->actingAs($this->kader)->from(route('presensi.index', $this->kegiatan))
            ->post(route('presensi.atur', [$this->kegiatan, $warga->nik]), ['hadir' => '1'])
            ->assertRedirect(route('presensi.index', $this->kegiatan))->assertSessionHas('sukses', 'Budi ditandai Hadir.');
    }

    public function test_nilai_aksi_tidak_valid_ditolak(): void
    {
        $warga = $this->buatWarga('3404000000000001');
        $this->atur($warga->nik, '2')->assertUnprocessable()->assertJsonValidationErrors('hadir');
        $this->actingAs($this->kader)->postJson(route('presensi.atur', [$this->kegiatan, '3404999999999999']), ['hadir' => '1'])->assertNotFound();
    }

    public function test_batalkan_ditolak_jika_warga_sudah_diperiksa(): void
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Siti']);
        $this->atur($warga->nik, '1');
        $this->buatPemeriksaan($warga, $this->kegiatan);

        $this->atur($warga->nik, '0')->assertStatus(422)->assertJson(['pesan' => 'Kehadiran Siti tidak bisa dibatalkan karena sudah mulai/selesai diperiksa.']);
        $this->assertTrue($this->kegiatan->kehadiran()->first()->status_kehadiran);
    }

    public function test_presensi_tidak_bisa_diubah_setelah_kegiatan_selesai(): void
    {
        $warga = $this->buatWarga('3404000000000001');
        $this->kegiatan->update(['status_kegiatan' => false]);

        $this->atur($warga->nik, '1')->assertStatus(422)->assertJson(['pesan' => 'Kegiatan ini sudah selesai sehingga presensi tidak bisa diubah lagi.']);
    }

    public function test_presensi_ditolak_setelah_lewat_24_jam(): void
    {
        $warga = $this->buatWarga('3404000000000001');
        $this->travel(24)->hours();

        $this->atur($warga->nik, '1')->assertStatus(422);
        $this->assertFalse($this->kegiatan->fresh()->status_kegiatan);
    }

    public function test_polling_menampilkan_perubahan_dari_meja_lain(): void
    {
        $a = $this->buatWarga('3404000000000001', ['nama_warga' => 'Ani']);
        $b = $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi']);
        $kader2 = $this->buatKader('kader_meja_2');

        $this->actingAs($this->kader)->getJson(route('presensi.status', $this->kegiatan))
            ->assertOk()->assertHeader('Cache-Control')
            ->assertExactJson(['berlangsung' => true, 'ringkasan' => ['total' => 2, 'hadir' => 0, 'tidak_hadir' => 2], 'kehadiran' => [$a->nik => false, $b->nik => false]]);

        $this->atur($b->nik, '1', $kader2); // meja 2 menandai Budi

        $this->actingAs($this->kader)->getJson(route('presensi.status', $this->kegiatan))
            ->assertJson(['ringkasan' => ['total' => 2, 'hadir' => 1, 'tidak_hadir' => 1], 'kehadiran' => [$b->nik => true]]);
    }

    public function test_polling_memberi_tahu_jika_kegiatan_sudah_selesai(): void
    {
        $this->travel(24)->hours();

        $this->actingAs($this->kader)->getJson(route('presensi.status', $this->kegiatan))->assertJson(['berlangsung' => false]);
    }

    public function test_pencarian_dan_filter_kategori_mengubah_ringkasan(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Ani Balita', 'tanggal_lahir' => '2023-01-01']);
        $dewasa = $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi Dewasa']);
        $this->buatWarga('3404000000000003', ['nama_warga' => 'Citra Dewasa']);
        $this->atur($dewasa->nik, '1');

        $this->actingAs($this->kader)->getJson(route('presensi.status', [$this->kegiatan, 'kategori' => 'dewasa']))
            ->assertJson(['ringkasan' => ['total' => 2, 'hadir' => 1, 'tidak_hadir' => 1]]);

        $this->actingAs($this->kader)->get(route('presensi.index', [$this->kegiatan, 'cari' => 'citra']))
            ->assertSee('Citra Dewasa')->assertDontSee('Budi Dewasa')->assertSee('id="jumlah-total">1<', false);

        $this->actingAs($this->kader)->get(route('presensi.index', [$this->kegiatan, 'kategori' => 'alien']))->assertSessionHasErrors('kategori');
    }

    public function test_arsip_hanya_lihat_dengan_status_tidak_hadir(): void
    {
        $hadir = $this->buatWarga('3404000000000001', ['nama_warga' => 'Ani']);
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi']);
        $this->atur($hadir->nik, '1');
        $this->actingAs($this->kader)->post(route('kegiatan.selesaikan', $this->kegiatan));

        // Warga baru yang terdaftar setelah kegiatan selesai tidak ikut masuk arsip.
        $this->buatWarga('3404000000000003', ['nama_warga' => 'Citra Baru']);

        $this->actingAs($this->kader)->get(route('presensi.index', $this->kegiatan))
            ->assertOk()->assertSee('status kehadiran hanya dapat dilihat')->assertSee('Kembali ke Arsip')->assertSee('Hanya-lihat')->assertSee('Tidak Hadir')
            ->assertDontSee('Tandai Hadir')->assertDontSee('Selesaikan Kegiatan')->assertDontSee('Citra Baru')
            ->assertSee('id="jumlah-total">2<', false)->assertSee('id="jumlah-hadir">1<', false);
    }

    public function test_kategori_di_arsip_dihitung_per_tanggal_kegiatan(): void
    {
        // Saat kegiatan (10-10-2026) masih 4 tahun → Balita; pada 1-11-2026 sudah 5 tahun → Remaja.
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Dika', 'tanggal_lahir' => '2021-10-20']);
        $this->actingAs($this->kader)->post(route('kegiatan.selesaikan', $this->kegiatan));
        $this->travelTo(CarbonImmutable::parse('2026-11-01 08:00:00'));

        $this->actingAs($this->kader)->getJson(route('presensi.status', [$this->kegiatan, 'kategori' => 'balita']))
            ->assertJson(['ringkasan' => ['total' => 1], 'kehadiran' => [$warga->nik => false]]);
    }

    public function test_warga_yang_hanya_tidak_hadir_tetap_bisa_dihapus(): void
    {
        $warga = $this->buatWarga('3404000000000001');
        $this->actingAs($this->kader)->post(route('kegiatan.selesaikan', $this->kegiatan));
        $this->assertSame(1, $this->kegiatan->kehadiran()->count());

        $this->actingAs($this->kader)->delete(route('warga.destroy', $warga))->assertSessionHas('sukses');
        $this->assertSame(0, $this->kegiatan->kehadiran()->count());
    }
}
