<?php

namespace Tests\Feature\Vaksinasi;

use App\Models\Kader;
use App\Models\Vaksin;
use App\Models\Vaksinasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Master Vaksin (Figma 192:320 & 208:894; Activity Diagram 2.21–2.24).
 */
class MasterVaksinTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-16 08:00:00'));
        $this->kader = $this->buatKader();
    }

    public function test_daftar_dan_pencarian(): void
    {
        Vaksin::create(['nama_vaksin' => 'BCG', 'deskripsi' => 'Pencegahan TBC berat']);
        Vaksin::create(['nama_vaksin' => 'Polio 1']);

        $this->actingAs($this->kader)->get(route('vaksin.index'))
            ->assertOk()->assertSee('Master Vaksin')->assertSee('+ Tambah Vaksin')
            ->assertSeeInOrder(['BCG', 'Pencegahan TBC berat', 'Polio 1'])
            ->assertSee('Tambah / Edit Vaksin');
        $this->actingAs($this->kader)->get(route('vaksin.index', ['cari' => 'pol']))
            ->assertSee('Polio 1')->assertDontSee('Pencegahan TBC berat');
        $this->actingAs($this->kader)->get(route('vaksin.index', ['cari' => '%']))
            ->assertSee('Tidak ada vaksin yang cocok dengan pencarian.');
    }

    public function test_tambah_vaksin_dengan_validasi(): void
    {
        Vaksin::create(['nama_vaksin' => 'BCG']);

        $this->actingAs($this->kader)->post(route('vaksin.store'), ['nama_vaksin' => '', '_mode' => 'tambah'])
            ->assertSessionHasErrors(['nama_vaksin' => 'Nama vaksin harus diisi.']);
        $this->actingAs($this->kader)->post(route('vaksin.store'), ['nama_vaksin' => 'bcg', '_mode' => 'tambah'])
            ->assertSessionHasErrors(['nama_vaksin' => 'Nama vaksin telah ada di daftar.']);
        $this->actingAs($this->kader)->post(route('vaksin.store'), ['nama_vaksin' => '<b>X</b>'])->assertSessionHasErrors('nama_vaksin');

        $this->actingAs($this->kader)->post(route('vaksin.store'), ['nama_vaksin' => 'PCV 1', 'deskripsi' => 'Pneumokokus dosis ke-1'])
            ->assertRedirect(route('vaksin.index'))->assertSessionHas('sukses');
        $this->assertDatabaseHas('vaksin', ['nama_vaksin' => 'PCV 1', 'deskripsi' => 'Pneumokokus dosis ke-1']);
    }

    public function test_popup_terbuka_lagi_bila_validasi_gagal(): void
    {
        $this->actingAs($this->kader)->from(route('vaksin.index'))->followingRedirects()
            ->post(route('vaksin.store'), ['nama_vaksin' => '', '_mode' => 'tambah'])
            ->assertSee('data-buka-otomatis', false)->assertSee('Nama vaksin harus diisi.');
    }

    public function test_edit_vaksin_dengan_versi(): void
    {
        $bcg = Vaksin::create(['nama_vaksin' => 'BCG']);
        Vaksin::create(['nama_vaksin' => 'IPV']);
        $versi = $bcg->updated_at->format('Y-m-d H:i:s');

        $this->actingAs($this->kader)->put(route('vaksin.update', $bcg), ['nama_vaksin' => 'IPV', 'versi' => $versi])
            ->assertSessionHasErrors(['nama_vaksin' => 'Nama vaksin telah ada di daftar.']);
        $this->actingAs($this->kader)->put(route('vaksin.update', $bcg), ['nama_vaksin' => 'BCG', 'deskripsi' => 'Dosis tunggal', 'versi' => $versi])
            ->assertSessionHas('sukses');
        $this->assertSame('Dosis tunggal', $bcg->refresh()->deskripsi);

        // Kader lain menyimpan dengan versi lama → ditolak.
        $this->travel(1)->minutes();
        $bcg->update(['deskripsi' => 'Diubah meja lain']);
        $this->actingAs($this->kader)->put(route('vaksin.update', $bcg), ['nama_vaksin' => 'BCG', 'deskripsi' => 'Tertimpa?', 'versi' => $versi])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'baru saja diubah'));
        $this->assertSame('Diubah meja lain', $bcg->refresh()->deskripsi);
    }

    public function test_hapus_vaksin_ditolak_bila_sudah_dipakai(): void
    {
        $bcg = Vaksin::create(['nama_vaksin' => 'BCG']);
        $ipv = Vaksin::create(['nama_vaksin' => 'IPV']);
        $balita = $this->buatWarga('3404000000000001', ['tanggal_lahir' => '2025-01-01']);
        Vaksinasi::create(['nik' => $balita->nik, 'id_vaksin' => $bcg->id_vaksin, 'status_vaksinasi' => true, 'tanggal_vaksinasi' => '2025-02-01']);

        $this->actingAs($this->kader)->get(route('vaksin.index'))->assertSee('Sudah tercatat pada 1 balita');
        $this->actingAs($this->kader)->delete(route('vaksin.destroy', $bcg))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'tidak bisa dihapus'));
        $this->assertModelExists($bcg);

        $this->actingAs($this->kader)->delete(route('vaksin.destroy', $ipv))->assertSessionHas('sukses');
        $this->assertModelMissing($ipv);
    }

    public function test_menu_master_vaksin_aktif_dan_wajib_login(): void
    {
        $this->get(route('vaksin.index'))->assertRedirect(route('login'));
        $html = $this->actingAs($this->kader)->get(route('vaksin.index'))->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"\s*><i aria-hidden="true"><\/i>Master Vaksin/', preg_replace('/\s+/', ' ', $html));
    }
}
