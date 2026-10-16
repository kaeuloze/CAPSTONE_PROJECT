<?php

namespace Tests\Feature\Vaksinasi;

use App\Enums\StatusAsi;
use App\Models\AsiEksklusif;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Vaksin;
use App\Models\Vaksinasi;
use App\Models\Warga;
use App\Services\VaksinasiService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Vaksinasi Balita & ASI Eksklusif (Figma 192:72 & 141:2678; Activity Diagram 2.19 & 2.20).
 */
class VaksinasiBalitaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $kegiatan;

    private Vaksin $hb0;

    private Vaksin $bcg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-16 08:00:00'));
        $this->kader = $this->buatKader();
        $this->kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Oktober', 'tanggal_kegiatan' => '2026-10-16', 'status_kegiatan' => true, 'waktu_dibuka' => now(),
        ]);
        $this->hb0 = Vaksin::create(['nama_vaksin' => 'Hepatitis B (HB-0)']);
        $this->bcg = Vaksin::create(['nama_vaksin' => 'BCG']);
    }

    private function hadir(Warga $warga): Warga
    {
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function bayi(string $nik = '3404000000000001', string $lahir = '2026-06-01', string $nama = 'Adik Bayi'): Warga
    {
        return $this->hadir($this->buatWarga($nik, ['nama_warga' => $nama, 'tanggal_lahir' => $lahir]));
    }

    /** Kirim form dengan sidik "awal" seperti saat halaman dibuka. */
    private function simpan(Warga $warga, array $vaksin = [], array $asi = [], ?array $awal = null)
    {
        $awal ??= $this->awal($warga);

        return $this->actingAs($this->kader)->post(route('vaksinasi.simpan', $warga->nik), compact('vaksin', 'asi', 'awal'));
    }

    private function awal(Warga $warga): array
    {
        [$vaksinasi, $asi] = app(VaksinasiService::class)->catatan($warga);
        $hasil = ['vaksin' => [], 'asi' => []];
        foreach (Vaksin::all() as $v) {
            $hasil['vaksin'][$v->id_vaksin] = VaksinasiService::sidikVaksin($vaksinasi->get($v->id_vaksin));
        }
        foreach (range(0, 6) as $b) {
            $hasil['asi'][$b] = VaksinasiService::sidikAsi($asi->get($b));
        }

        return $hasil;
    }

    // ---------- Daftar ----------

    public function test_daftar_hanya_balita_yang_hadir_di_kegiatan_aktif(): void
    {
        $this->bayi();
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Balita Belum Hadir', 'tanggal_lahir' => '2025-01-01']);
        $this->hadir($this->buatWarga('3404000000000003', ['nama_warga' => 'Remaja Hadir', 'tanggal_lahir' => '2015-01-01']));

        $this->actingAs($this->kader)->get(route('vaksinasi.index'))
            ->assertOk()->assertSee('Kelola Vaksinasi Balita')->assertSee('Kegiatan aktif: Posyandu Oktober')
            ->assertSee('Adik Bayi')->assertSee('Buka')
            ->assertDontSee('Balita Belum Hadir')->assertDontSee('Remaja Hadir');

        $this->actingAs($this->kader)->getJson(route('vaksinasi.status'))
            ->assertJson(['aktif' => true, 'total' => 1, 'baris' => [['nama' => 'Adik Bayi']]]);
        $this->actingAs($this->kader)->get(route('vaksinasi.index', ['cari' => 'tidak ada']))
            ->assertSee('Tidak ada balita yang cocok dengan pencarian.');
    }

    public function test_tanpa_kegiatan_aktif(): void
    {
        $this->kegiatan->update(['status_kegiatan' => false]);

        $this->actingAs($this->kader)->get(route('vaksinasi.index'))->assertSee('Belum ada kegiatan posyandu yang aktif');
        $this->actingAs($this->kader)->getJson(route('vaksinasi.status'))->assertJson(['aktif' => false]);
    }

    public function test_bukan_balita_atau_belum_hadir_tidak_bisa_dibuka(): void
    {
        $remaja = $this->hadir($this->buatWarga('3404000000000003', ['nama_warga' => 'Remaja', 'tanggal_lahir' => '2015-01-01']));
        $belum = $this->buatWarga('3404000000000004', ['nama_warga' => 'Belum Hadir', 'tanggal_lahir' => '2025-01-01']);

        $this->actingAs($this->kader)->get(route('vaksinasi.form', $remaja->nik))
            ->assertRedirect(route('vaksinasi.index'))->assertSessionHas('galat', fn ($p) => str_contains($p, 'bukan balita'));
        $this->actingAs($this->kader)->get(route('vaksinasi.form', $belum->nik))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'belum tercatat Hadir'));
        $this->simpan($belum, [$this->bcg->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-10-01']])
            ->assertSessionHas('galat');
        $this->assertSame(0, Vaksinasi::count());
    }

    // ---------- Form ----------

    public function test_form_menampilkan_checklist_dan_asi_bulan_0_sampai_6(): void
    {
        $bayi = $this->bayi(); // usia 4 bulan
        Vaksinasi::create(['nik' => $bayi->nik, 'id_vaksin' => $this->hb0->id_vaksin, 'status_vaksinasi' => true, 'tanggal_vaksinasi' => '2026-06-01']);

        $this->actingAs($this->kader)->get(route('vaksinasi.form', $bayi->nik))
            ->assertOk()
            ->assertSee('Usia 4 bulan')->assertSee('Imunisasi Dasar (per dosis)')
            ->assertSeeInOrder(['Hepatitis B (HB-0)', 'Sudah', 'value="2026-06-01"', 'BCG', 'Belum'], false)
            ->assertSee('ASI Eksklusif')->assertSee('Bulan 0')->assertSee('Bulan 6')->assertSee('Tidak Recall');
    }

    public function test_asi_disembunyikan_bila_usia_lewat_6_bulan(): void
    {
        $balita = $this->bayi(lahir: '2026-03-01'); // 7 bulan

        $this->actingAs($this->kader)->get(route('vaksinasi.form', $balita->nik))
            ->assertSee('ASI Eksklusif tidak ditampilkan')->assertDontSee('name="asi[0]"', false);

        $this->simpan($balita, [], ['2' => 'ya'])->assertSessionHas('sukses');
        $this->assertSame(0, AsiEksklusif::count()); // isian ASI diabaikan
    }

    // ---------- Simpan ----------

    public function test_centang_dosis_dengan_tanggal_buku_kia(): void
    {
        $bayi = $this->bayi();

        $this->simpan($bayi, [
            $this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-06-01'],
            $this->bcg->id_vaksin => ['sudah' => '0', 'tanggal' => null],
        ])->assertRedirect(route('vaksinasi.index'))->assertSessionHas('sukses', fn ($p) => str_contains($p, '1 perubahan'));

        $v = Vaksinasi::sole();
        $this->assertTrue($v->status_vaksinasi);
        $this->assertSame('2026-06-01', $v->tanggal_vaksinasi->toDateString());
        $this->assertSame($this->kegiatan->id_kegiatan, $v->id_kegiatan);
    }

    public function test_tanggal_wajib_dan_tidak_boleh_di_luar_rentang(): void
    {
        $bayi = $this->bayi();

        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => null]])
            ->assertSessionHasErrors(["vaksin.{$this->hb0->id_vaksin}.tanggal" => 'Isi tanggal pemberian sesuai Buku KIA.']);
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-05-01']]) // sebelum lahir
            ->assertSessionHasErrors("vaksin.{$this->hb0->id_vaksin}.tanggal");
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-10-17']]) // setelah kegiatan
            ->assertSessionHasErrors("vaksin.{$this->hb0->id_vaksin}.tanggal");
        $this->assertSame(0, Vaksinasi::count());
    }

    public function test_centang_dibatalkan_menghapus_catatan(): void
    {
        $bayi = $this->bayi();
        Vaksinasi::create(['nik' => $bayi->nik, 'id_vaksin' => $this->bcg->id_vaksin, 'status_vaksinasi' => true, 'tanggal_vaksinasi' => '2026-07-01', 'id_kegiatan' => $this->kegiatan->id_kegiatan]);

        $this->simpan($bayi, [$this->bcg->id_vaksin => ['sudah' => '0']])->assertSessionHas('sukses');
        $this->assertSame(0, Vaksinasi::count());
    }

    public function test_asi_disimpan_diubah_dan_dikosongkan(): void
    {
        $bayi = $this->bayi();

        $this->simpan($bayi, [], ['0' => 'ya', '1' => 'tidak_recall', '2' => ''])->assertSessionHas('sukses');
        $this->assertSame(2, AsiEksklusif::count());
        $this->assertSame(StatusAsi::TidakRecall, AsiEksklusif::where('bulan_ke', 1)->sole()->status_asi);
        $this->assertSame('2026-10-16', AsiEksklusif::where('bulan_ke', 0)->sole()->tanggal_dicatat->toDateString());

        $this->simpan($bayi, [], ['1' => 'ya', '0' => ''])->assertSessionHas('sukses');
        $this->assertSame(StatusAsi::Ya, AsiEksklusif::sole()->status_asi);
        $this->assertSame(1, AsiEksklusif::sole()->bulan_ke);

        $this->simpan($bayi, [], ['3' => 'bukan'])->assertSessionHasErrors('asi.3');
    }

    public function test_dua_meja_baris_berbeda_aman_baris_sama_ditolak(): void
    {
        $bayi = $this->bayi();
        $awalBersama = $this->awal($bayi);

        // Meja A mencentang HB-0, meja B mencentang BCG dari halaman yang dibuka bersamaan → keduanya tersimpan.
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-06-01'], $this->bcg->id_vaksin => ['sudah' => '0']], [], $awalBersama)
            ->assertSessionHas('sukses');
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '0'], $this->bcg->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-07-01']], [], $awalBersama)
            ->assertSessionHas('sukses');
        $this->assertSame(2, Vaksinasi::count()); // HB-0 dari meja A tidak terhapus oleh meja B

        // Meja B mengubah tanggal HB-0 padahal meja A sudah mengubahnya → ditolak.
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-06-02']], [], $awalBersama)
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Hepatitis B (HB-0)'));
        $this->assertSame('2026-06-01', Vaksinasi::where('id_vaksin', $this->hb0->id_vaksin)->sole()->tanggal_vaksinasi->toDateString());
    }

    public function test_batalkan_hadir_ditolak_bila_sudah_ada_catatan_vaksin(): void
    {
        $bayi = $this->bayi();
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-06-01']])->assertSessionHas('sukses');

        $this->actingAs($this->kader)->post(route('presensi.atur', [$this->kegiatan->id_kegiatan, $bayi->nik]), ['hadir' => '0'])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'catatan vaksinasi/ASI'));
    }

    // ---------- Riwayat ----------

    public function test_riwayat_menampilkan_vaksin_dan_asi_serta_kunjungan_terakhir(): void
    {
        $bayi = $this->bayi();
        $this->simpan($bayi, [$this->hb0->id_vaksin => ['sudah' => '1', 'tanggal' => '2026-06-01']], ['3' => 'ya'])->assertSessionHas('sukses');

        $this->actingAs($this->kader)->get(route('riwayat.index'))->assertSee('16/10/2026');
        $this->actingAs($this->kader)->get(route('riwayat.warga', $bayi->nik))
            ->assertSee('Riwayat Vaksinasi &amp; ASI Eksklusif', false)
            ->assertSee('16 Oktober 2026')->assertSee('Posyandu Oktober')
            ->assertSee('Vaksinasi: Hepatitis B (HB-0) (1 Jun 2026) · ASI Bulan 3: Ya');
    }
}
