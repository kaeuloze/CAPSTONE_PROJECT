<?php

namespace Tests\Feature\Kegiatan;

use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Menu Kegiatan: daftar (tab Berlangsung/Arsip), buat, edit/buka kembali, selesaikan, hapus,
 * penutupan otomatis 24 jam (SKF-06; Activity Diagram 2.5, 2.6, 2.8, 2.9; revisi no. 7).
 */
class KegiatanTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00'));
        $this->kader = $this->buatKader();
    }

    private function kegiatan(array $ubah = []): Kegiatan
    {
        return Kegiatan::create(array_merge([
            'nama_kegiatan' => 'Posyandu Oktober 2026',
            'tanggal_kegiatan' => '2026-10-10',
            'status_kegiatan' => true,
            'waktu_dibuka' => now(),
        ], $ubah));
    }

    private function versi(Kegiatan $k): string
    {
        return $k->fresh()->updated_at->format('Y-m-d H:i:s');
    }

    public function test_tamu_tidak_bisa_membuka_menu_kegiatan(): void
    {
        $k = $this->kegiatan();
        $this->get(route('kegiatan.index'))->assertRedirect(route('login'));
        $this->post(route('kegiatan.store'), [])->assertRedirect(route('login'));
        $this->get(route('presensi.index', $k))->assertRedirect(route('login'));
        $this->getJson(route('presensi.status', $k))->assertUnauthorized();
    }

    public function test_daftar_kegiatan_tab_berlangsung_dan_arsip(): void
    {
        $this->kegiatan(['nama_kegiatan' => 'Posyandu Aktif']);
        $this->kegiatan(['nama_kegiatan' => 'Posyandu September', 'status_kegiatan' => false, 'tanggal_kegiatan' => '2026-09-16']);
        $this->buatWarga('3404000000000001');
        $this->buatWarga('3404000000000002');

        $this->actingAs($this->kader)->get(route('kegiatan.index'))
            ->assertOk()->assertSee('Kelola Kegiatan')->assertSee('Posyandu Aktif')->assertDontSee('Posyandu September')
            ->assertSee('Sabtu, 10 Oktober 2026')->assertSee('Total: 2')->assertSee('Presensi');

        $this->actingAs($this->kader)->get(route('kegiatan.index', ['tab' => 'arsip']))
            ->assertOk()->assertSee('Posyandu September')->assertDontSee('Posyandu Aktif')->assertSee('Lihat Presensi');
    }

    public function test_buat_dan_mulai_kegiatan(): void
    {
        $this->actingAs($this->kader)
            ->post(route('kegiatan.store'), ['nama_kegiatan' => '  Posyandu   Oktober 2026 ', 'tanggal_kegiatan' => '2026-10-10'])
            ->assertRedirect(route('kegiatan.index'))->assertSessionHas('sukses');

        $k = Kegiatan::first();
        $this->assertSame('Posyandu Oktober 2026', $k->nama_kegiatan);
        $this->assertTrue($k->status_kegiatan);
        $this->assertSame('2026-10-10 08:00:00', $k->waktu_dibuka->toDateTimeString());
    }

    public function test_validasi_buat_kegiatan(): void
    {
        $this->actingAs($this->kader)->post(route('kegiatan.store'), [])
            ->assertSessionHasErrors(['nama_kegiatan', 'tanggal_kegiatan']);
        $this->actingAs($this->kader)->post(route('kegiatan.store'), ['nama_kegiatan' => 'X', 'tanggal_kegiatan' => '2026-10-11'])
            ->assertSessionHasErrors(['tanggal_kegiatan' => 'Tanggal pelaksanaan tidak boleh melewati hari ini.']);
        $this->actingAs($this->kader)->post(route('kegiatan.store'), ['nama_kegiatan' => '<script>', 'tanggal_kegiatan' => '2026-10-10'])
            ->assertSessionHasErrors('nama_kegiatan');
        $this->actingAs($this->kader)->post(route('kegiatan.store'), ['nama_kegiatan' => 'X', 'tanggal_kegiatan' => '2026-10-10', 'status_kegiatan' => '0'])
            ->assertSessionHasErrors('status_kegiatan');

        // Tanggal yang sudah lewat boleh (mencatat kegiatan yang lupa dibuat).
        $this->actingAs($this->kader)->post(route('kegiatan.store'), ['nama_kegiatan' => 'Posyandu Lalu', 'tanggal_kegiatan' => '2026-10-01'])
            ->assertSessionHasNoErrors();
    }

    public function test_hanya_satu_kegiatan_boleh_berlangsung(): void
    {
        $this->kegiatan(['nama_kegiatan' => 'Posyandu Aktif']);

        $this->actingAs($this->kader)->get(route('kegiatan.create'))->assertSee('Masih ada kegiatan yang berlangsung');
        $this->actingAs($this->kader)
            ->post(route('kegiatan.store'), ['nama_kegiatan' => 'Posyandu Kedua', 'tanggal_kegiatan' => '2026-10-10'])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Posyandu Aktif'));

        $this->assertSame(1, Kegiatan::count());
    }

    public function test_selesaikan_kegiatan_mencatat_tidak_hadir(): void
    {
        $k = $this->kegiatan();
        $hadir = $this->buatWarga('3404000000000001');
        $this->buatWarga('3404000000000002');
        $this->buatWarga('3404000000000003');
        $k->kehadiran()->create(['nik' => $hadir->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        $this->actingAs($this->kader)->post(route('kegiatan.selesaikan', $k))
            ->assertRedirect(route('kegiatan.index', ['tab' => 'arsip']))->assertSessionHas('sukses');

        $this->assertFalse($k->fresh()->status_kegiatan);
        $this->assertSame(3, $k->kehadiran()->count());
        $this->assertSame(1, $k->kehadiran()->where('status_kehadiran', true)->count());
        $this->assertSame(2, $k->kehadiran()->where('status_kehadiran', false)->count());

        // Diselesaikan dua kali (mis. dua kader menekan bersamaan) tidak membuat data ganda.
        $this->actingAs($this->kader)->post(route('kegiatan.selesaikan', $k));
        $this->assertSame(3, $k->kehadiran()->count());
    }

    public function test_kegiatan_otomatis_selesai_setelah_24_jam(): void
    {
        $k = $this->kegiatan();
        $this->buatWarga('3404000000000001');

        $this->travelTo(CarbonImmutable::parse('2026-10-11 07:59:00'));
        $this->actingAs($this->kader)->get(route('kegiatan.index'));
        $this->assertTrue($k->fresh()->status_kegiatan);

        $this->travelTo(CarbonImmutable::parse('2026-10-11 08:00:00'));
        $this->actingAs($this->kader)->get(route('kegiatan.index'))->assertSee('Belum ada kegiatan yang berlangsung');
        $this->assertFalse($k->fresh()->status_kegiatan);
        $this->assertSame(1, $k->kehadiran()->where('status_kehadiran', false)->count());
    }

    public function test_perintah_terjadwal_menutup_kegiatan(): void
    {
        $k = $this->kegiatan();
        $this->travel(25)->hours();

        $this->artisan('kegiatan:tutup-otomatis')->expectsOutput('Kegiatan yang ditutup otomatis: 1.')->assertSuccessful();
        $this->assertFalse($k->fresh()->status_kegiatan);
        $this->artisan('schedule:list')->expectsOutputToContain('kegiatan:tutup-otomatis');
    }

    public function test_edit_nama_tanggal_dan_status_selesai(): void
    {
        $k = $this->kegiatan();
        $this->buatWarga('3404000000000001');

        $this->actingAs($this->kader)->get(route('kegiatan.edit', $k))->assertOk()->assertSee('Detail Kegiatan')->assertSee('Hapus Kegiatan ini');

        $this->actingAs($this->kader)->put(route('kegiatan.update', $k), [
            'nama_kegiatan' => 'Posyandu Okt', 'tanggal_kegiatan' => '2026-10-09', 'status_kegiatan' => '0', 'versi' => $this->versi($k),
        ])->assertRedirect(route('kegiatan.index', ['tab' => 'arsip']));

        $segar = $k->fresh();
        $this->assertSame('Posyandu Okt', $segar->nama_kegiatan);
        $this->assertSame('2026-10-09', $segar->tanggal_kegiatan->toDateString());
        $this->assertFalse($segar->status_kegiatan);
        $this->assertSame(1, $segar->kehadiran()->where('status_kehadiran', false)->count());
    }

    public function test_buka_kembali_dari_arsip_mengulang_hitungan_24_jam(): void
    {
        $k = $this->kegiatan(['status_kegiatan' => false, 'waktu_dibuka' => now()->subDays(3), 'tanggal_kegiatan' => '2026-10-07']);
        $warga = $this->buatWarga('3404000000000001');
        $k->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => false]);

        $this->actingAs($this->kader)->put(route('kegiatan.update', $k), [
            'nama_kegiatan' => $k->nama_kegiatan, 'tanggal_kegiatan' => '2026-10-07', 'status_kegiatan' => '1', 'versi' => $this->versi($k),
        ])->assertSessionHas('sukses');

        $segar = $k->fresh();
        $this->assertTrue($segar->status_kegiatan);
        $this->assertSame('2026-10-10 08:00:00', $segar->waktu_dibuka->toDateTimeString());

        // Warga yang tadinya Tidak Hadir kembali tampil "Belum" dan bisa ditandai hadir.
        $this->actingAs($this->kader)->get(route('presensi.index', $k))->assertSee('Belum')->assertSee('Tandai Hadir');
    }

    public function test_buka_kembali_ditolak_jika_ada_kegiatan_lain_berlangsung(): void
    {
        $this->kegiatan(['nama_kegiatan' => 'Posyandu Aktif']);
        $arsip = $this->kegiatan(['nama_kegiatan' => 'Posyandu Lama', 'status_kegiatan' => false]);

        $this->actingAs($this->kader)->put(route('kegiatan.update', $arsip), [
            'nama_kegiatan' => 'Posyandu Lama', 'tanggal_kegiatan' => '2026-10-10', 'status_kegiatan' => '1', 'versi' => $this->versi($arsip),
        ])->assertSessionHas('galat', fn ($p) => str_contains($p, 'Posyandu Aktif'));

        $this->assertFalse($arsip->fresh()->status_kegiatan);
    }

    public function test_edit_bersamaan_ditolak(): void
    {
        $k = $this->kegiatan();
        $versi = $this->versi($k);
        $this->travel(5)->seconds();
        $data = ['tanggal_kegiatan' => '2026-10-10', 'status_kegiatan' => '1', 'versi' => $versi];

        $this->actingAs($this->kader)->put(route('kegiatan.update', $k), $data + ['nama_kegiatan' => 'Dari meja 1'])->assertSessionHas('sukses');
        $this->travel(5)->seconds();
        $this->actingAs($this->kader)->put(route('kegiatan.update', $k), $data + ['nama_kegiatan' => 'Dari meja 2'])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'diubah oleh kader lain'));

        $this->assertSame('Dari meja 1', $k->fresh()->nama_kegiatan);
    }

    public function test_hapus_kegiatan_yang_belum_ada_kehadiran(): void
    {
        $k = $this->kegiatan(['status_kegiatan' => false]);
        $warga = $this->buatWarga('3404000000000001');
        $k->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => false]); // hanya Tidak Hadir

        $this->actingAs($this->kader)->delete(route('kegiatan.destroy', $k))->assertSessionHas('sukses');

        $this->assertNull($k->fresh());
        $this->assertSame(0, Kehadiran::count());
    }

    public function test_hapus_kegiatan_ditolak_jika_sudah_ada_yang_hadir_atau_diperiksa(): void
    {
        $k = $this->kegiatan();
        $warga = $this->buatWarga('3404000000000001');
        $k->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        $this->actingAs($this->kader)->from(route('kegiatan.edit', $k))->delete(route('kegiatan.destroy', $k))
            ->assertRedirect(route('kegiatan.edit', $k))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'tidak bisa dihapus'));

        $k2 = $this->kegiatan(['status_kegiatan' => false, 'nama_kegiatan' => 'Lain']);
        $this->buatPemeriksaan($warga, $k2);
        $this->actingAs($this->kader)->delete(route('kegiatan.destroy', $k2))->assertSessionHas('galat');

        $this->assertSame(2, Kegiatan::count());
    }
}
