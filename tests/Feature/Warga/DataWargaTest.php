<?php

namespace Tests\Feature\Warga;

use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use App\Models\Kader;
use App\Models\Penyakit;
use App\Models\RiwayatPtm;
use App\Models\Warga;
use Database\Seeders\PenyakitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Menu Data Warga: lihat, cari/filter, tambah, edit, hapus, validasi, akses
 * (SKF-04, SKF-05; Activity Diagram 2.29–2.32; SOP Capstone poin 5–7).
 */
class DataWargaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PenyakitSeeder::class);
        $this->kader = $this->buatKader();
    }

    private function dataForm(array $ubah = []): array
    {
        return array_merge([
            'nama_warga' => 'Siti Aminah',
            'nik' => '3404012345678901',
            'tanggal_lahir' => now()->subYears(30)->toDateString(),
            'no_hp' => '0812-3456-7890',
            'jenis_kelamin' => '0',
            'alamat' => 'Tegalyoso RT 02',
            'status_kondisi' => 'normal',
            'penyakit' => [],
        ], $ubah);
    }

    private function idPenyakit(string $nama): int
    {
        return Penyakit::where('nama_penyakit', $nama)->value('id_penyakit');
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login_untuk_semua_halaman_warga(): void
    {
        $warga = $this->buatWarga();
        $this->get(route('warga.index'))->assertRedirect(route('login'));
        $this->get(route('warga.create'))->assertRedirect(route('login'));
        $this->get(route('warga.show', $warga))->assertRedirect(route('login'));
        $this->post(route('warga.store'), $this->dataForm())->assertRedirect(route('login'));
        $this->delete(route('warga.destroy', $warga))->assertRedirect(route('login'));
        $this->assertDatabaseCount('warga', 1);
    }

    // ---------- Lihat, cari, filter ----------

    public function test_daftar_warga_menampilkan_data_dan_badge_kategori(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Lansia', 'tanggal_lahir' => now()->subYears(65)->toDateString()]);
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Ani Hamil', 'status_kondisi' => 'hamil']);

        $this->actingAs($this->kader)->get(route('warga.index'))
            ->assertOk()
            ->assertSee('Kelola Data Warga')
            ->assertSee('Budi Lansia')->assertSee('Lansia')
            ->assertSee('Ani Hamil')->assertSee('Ibu Hamil');
    }

    public function test_pencarian_nama_dan_nik(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Santoso']);
        $this->buatWarga('3471000000000002', ['nama_warga' => 'Citra Lestari']);

        $this->actingAs($this->kader)->get(route('warga.index', ['cari' => 'budi']))
            ->assertSee('Budi Santoso')->assertDontSee('Citra Lestari');

        $this->actingAs($this->kader)->get(route('warga.index', ['cari' => '3471']))
            ->assertSee('Citra Lestari')->assertDontSee('Budi Santoso');
    }

    public function test_pencarian_karakter_khusus_tidak_menjadi_wildcard(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Santoso']);

        $this->actingAs($this->kader)->get(route('warga.index', ['cari' => '%']))
            ->assertOk()->assertDontSee('Budi Santoso')->assertSee('Tidak ada warga yang cocok');
    }

    public function test_filter_kategori_usia(): void
    {
        $this->buatWarga('3404000000000001', ['nama_warga' => 'Anak Balita', 'tanggal_lahir' => now()->subYears(3)->toDateString()]);
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Orang Dewasa']);

        $this->actingAs($this->kader)->get(route('warga.index', ['kategori' => 'balita']))
            ->assertSee('Anak Balita')->assertDontSee('Orang Dewasa');
    }

    public function test_filter_kategori_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->kader)->get(route('warga.index', ['kategori' => 'alien']))
            ->assertSessionHasErrors('kategori');
    }

    public function test_daftar_warga_dipaginasi(): void
    {
        config(['posyandu.warga.per_halaman' => 15]);
        for ($i = 1; $i <= 17; $i++) {
            $this->buatWarga('34040000000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), ['nama_warga' => 'Warga '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $this->actingAs($this->kader)->get(route('warga.index'))
            ->assertSee('Warga 15')->assertDontSee('Warga 16')->assertSee('Menampilkan 1–15 dari 17 warga');
        $this->actingAs($this->kader)->get(route('warga.index', ['page' => 2]))
            ->assertSee('Warga 17')->assertDontSee('Warga 01');
    }

    public function test_halaman_detail_warga(): void
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Santoso', 'no_hp' => '081234567890']);

        $this->actingAs($this->kader)->get(route('warga.show', $warga))
            ->assertOk()->assertSee('Detail Warga')->assertSee('Budi Santoso')->assertSee('081234567890')->assertSee('Hipertensi');
    }

    public function test_nik_tidak_terdaftar_menghasilkan_404(): void
    {
        $this->actingAs($this->kader)->get('warga/3404999999999999')->assertNotFound();
    }

    // ---------- Tambah ----------

    public function test_halaman_tambah_warga(): void
    {
        $this->actingAs($this->kader)->get(route('warga.create'))
            ->assertOk()->assertSee('Tambah Warga Baru')->assertSee('Kolesterol Tinggi')->assertSee('Tandai Ibu Hamil / Nifas');
    }

    public function test_tambah_warga_beserta_riwayat_ptm(): void
    {
        $this->actingAs($this->kader)
            ->post(route('warga.store'), $this->dataForm([
                'penyakit' => [$this->idPenyakit('Hipertensi'), $this->idPenyakit('Asma')],
            ]))
            ->assertRedirect(route('warga.index'))
            ->assertSessionHas('sukses');

        $warga = Warga::find('3404012345678901');
        $this->assertSame('Siti Aminah', $warga->nama_warga);
        $this->assertSame('081234567890', $warga->no_hp); // tanda hubung dibuang
        $this->assertFalse($warga->jenis_kelamin);
        $this->assertSame(KategoriUsia::Dewasa, $warga->kategori_usia);
        $this->assertSame(StatusKondisi::Normal, $warga->status_kondisi);
        $this->assertEqualsCanonicalizing(['Hipertensi', 'Asma'], $warga->penyakit->pluck('nama_penyakit')->all());
        $this->assertSame('kartu_bantu', RiwayatPtm::first()->getRawOriginal('sumber_riwayat'));
        $this->assertSame(now()->toDateString(), RiwayatPtm::first()->tanggal_dicatat->toDateString());
    }

    public function test_tambah_warga_ibu_hamil(): void
    {
        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataForm(['status_kondisi' => 'hamil']))
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusKondisi::Hamil, Warga::find('3404012345678901')->status_kondisi);
    }

    public function test_isian_wajib_harus_diisi(): void
    {
        $this->actingAs($this->kader)->post(route('warga.store'), [])
            ->assertSessionHasErrors(['nama_warga', 'nik', 'tanggal_lahir', 'jenis_kelamin', 'alamat']);
        $this->assertDatabaseCount('warga', 0);
    }

    public function test_validasi_format_isian(): void
    {
        $kasus = [
            'nik' => ['12345', '34040123456789AB', '34040123456789012'],
            'tanggal_lahir' => [now()->addDay()->toDateString(), '1899-12-31', '10/10/2000'],
            'no_hp' => ['12345', '0712345678', '08123'],
            'nama_warga' => ['<script>alert(1)</script>', str_repeat('a', 101)],
            'alamat' => ['<b>Tegalyoso</b>', str_repeat('a', 256)],
            'jenis_kelamin' => ['2', 'L'],
            'status_kondisi' => ['pacaran'],
            'penyakit' => [['9999'], ['abc']],
        ];

        foreach ($kasus as $isian => $daftarNilai) {
            foreach ($daftarNilai as $nilai) {
                $this->actingAs($this->kader)
                    ->post(route('warga.store'), $this->dataForm([$isian => $nilai]))
                    ->assertSessionHasErrors($isian === 'penyakit' ? 'penyakit.0' : $isian);
            }
        }
        $this->assertDatabaseCount('warga', 0);
    }

    public function test_nik_ganda_ditolak(): void
    {
        $this->buatWarga('3404012345678901');

        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataForm())
            ->assertSessionHasErrors(['nik' => 'NIK ini sudah terdaftar atas nama warga lain.']);
    }

    public function test_kondisi_khusus_ditolak_untuk_laki_laki_dan_balita(): void
    {
        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataForm(['jenis_kelamin' => '1', 'status_kondisi' => 'hamil']))
            ->assertSessionHasErrors('status_kondisi');

        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataForm([
            'tanggal_lahir' => now()->subYears(3)->toDateString(), 'status_kondisi' => 'nifas',
        ]))->assertSessionHasErrors('status_kondisi');

        // Lansia perempuan boleh (keputusan: semua warga perempuan kecuali balita).
        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataForm([
            'tanggal_lahir' => now()->subYears(62)->toDateString(), 'status_kondisi' => 'menyusui',
        ]))->assertSessionHasNoErrors();
    }

    // ---------- Edit ----------

    private function versi(Warga $warga): string
    {
        return $warga->fresh()->updated_at->format('Y-m-d H:i:s');
    }

    public function test_halaman_edit_terisi_data_lama(): void
    {
        $warga = $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi Santoso']);

        $this->actingAs($this->kader)->get(route('warga.edit', $warga))
            ->assertOk()->assertSee('Edit Data Warga')->assertSee('value="Budi Santoso"', false)
            ->assertSee('name="versi"', false);
    }

    public function test_edit_warga_dan_riwayat_ptm(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $warga->riwayatPtm()->create(['id_penyakit' => $this->idPenyakit('Asma'), 'tanggal_dicatat' => '2025-01-01']);
        $warga->riwayatPtm()->create(['id_penyakit' => $this->idPenyakit('Stroke'), 'tanggal_dicatat' => '2025-01-01']);

        $this->actingAs($this->kader)
            ->put(route('warga.update', $warga), $this->dataForm([
                'nama_warga' => 'Siti Aminah Baru',
                'tanggal_lahir' => now()->subYears(61)->toDateString(),
                'penyakit' => [$this->idPenyakit('Asma'), $this->idPenyakit('Kanker')],
                'versi' => $this->versi($warga),
            ]))
            ->assertRedirect(route('warga.index'))->assertSessionHas('sukses');

        $segar = $warga->fresh();
        $this->assertSame('Siti Aminah Baru', $segar->nama_warga);
        $this->assertSame(KategoriUsia::Lansia, $segar->kategori_usia); // dihitung ulang
        $ptm = $segar->riwayatPtm()->with('penyakit')->get()->keyBy(fn ($r) => $r->penyakit->nama_penyakit);
        $this->assertEqualsCanonicalizing(['Asma', 'Kanker'], $ptm->keys()->all());
        $this->assertSame('2025-01-01', $ptm['Asma']->tanggal_dicatat->toDateString()); // tanggal lama dipertahankan
    }

    public function test_edit_boleh_memakai_nik_sendiri_tetapi_tidak_nik_warga_lain(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $this->buatWarga('3404000000000002');

        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm(['versi' => $this->versi($warga)]))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm(['nik' => '3404000000000002', 'versi' => $this->versi($warga)]))
            ->assertSessionHasErrors('nik');
    }

    public function test_koreksi_nik_ikut_memperbarui_data_terkait(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $warga->riwayatPtm()->create(['id_penyakit' => $this->idPenyakit('Asma'), 'tanggal_dicatat' => '2025-01-01']);
        $this->buatPemeriksaan($warga, $this->buatKegiatan());

        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm([
            'nik' => '3404012345678999', 'penyakit' => [$this->idPenyakit('Asma')], 'versi' => $this->versi($warga),
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Warga::find('3404012345678901'));
        $baru = Warga::find('3404012345678999');
        $this->assertSame(1, $baru->pemeriksaan()->count());
        $this->assertSame(1, $baru->riwayatPtm()->count());
    }

    public function test_edit_bersamaan_kader_kedua_ditolak_dan_perubahan_pertama_tetap_aman(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $versiSaatDibuka = $this->versi($warga); // kedua kader membuka formulir pada versi yang sama
        $kader2 = $this->buatKader('kader_meja_2');

        $this->travel(5)->seconds();
        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm(['alamat' => 'Alamat dari meja 1', 'versi' => $versiSaatDibuka]))
            ->assertSessionHas('sukses');

        $this->travel(5)->seconds();
        $this->actingAs($kader2)->from(route('warga.edit', $warga))
            ->put(route('warga.update', $warga), $this->dataForm(['alamat' => 'Alamat dari meja 2', 'versi' => $versiSaatDibuka]))
            ->assertRedirect(route('warga.edit', $warga))
            ->assertSessionHas('galat', fn ($pesan) => str_contains($pesan, 'diubah oleh kader lain'));

        $this->assertSame('Alamat dari meja 1', $warga->fresh()->alamat);
    }

    public function test_perubahan_hanya_riwayat_ptm_juga_mengganti_versi(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $versiLama = $this->versi($warga);

        $this->travel(5)->seconds();
        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm([
            'nama_warga' => 'Warga Uji', 'alamat' => 'Tegalyoso', 'no_hp' => '', 'penyakit' => [$this->idPenyakit('DM')], 'versi' => $versiLama,
        ]))->assertSessionHas('sukses');

        $this->assertNotSame($versiLama, $this->versi($warga));
    }

    public function test_edit_tanpa_versi_ditolak(): void
    {
        $warga = $this->buatWarga('3404012345678901');

        $this->actingAs($this->kader)->put(route('warga.update', $warga), $this->dataForm())
            ->assertSessionHasErrors('versi');
    }

    // ---------- Hapus ----------

    public function test_hapus_warga_beserta_riwayat_ptm(): void
    {
        $warga = $this->buatWarga('3404012345678901');
        $warga->riwayatPtm()->create(['id_penyakit' => $this->idPenyakit('Asma'), 'tanggal_dicatat' => '2025-01-01']);

        $this->actingAs($this->kader)->delete(route('warga.destroy', $warga))
            ->assertRedirect(route('warga.index'))->assertSessionHas('sukses');

        $this->assertDatabaseMissing('warga', ['nik' => '3404012345678901']);
        $this->assertDatabaseCount('riwayat_ptm', 0);
        $this->assertDatabaseCount('penyakit', 7); // master penyakit tidak ikut terhapus
    }

    public function test_hapus_ditolak_jika_warga_sudah_punya_data_kegiatan(): void
    {
        $warga = $this->buatWarga('3404012345678901', ['nama_warga' => 'Budi Santoso']);
        $warga->kehadiran()->create(['id_kegiatan' => $this->buatKegiatan()->id_kegiatan, 'status_kehadiran' => true]);

        $this->actingAs($this->kader)->from(route('warga.index'))->delete(route('warga.destroy', $warga))
            ->assertRedirect(route('warga.index'))
            ->assertSessionHas('galat', fn ($pesan) => str_contains($pesan, 'Budi Santoso tidak bisa dihapus'));

        $this->assertDatabaseHas('warga', ['nik' => '3404012345678901']);
    }

    public function test_rute_warga_dilindungi_middleware_web_dan_auth(): void
    {
        // Middleware "web" memuat pengecekan token CSRF; "auth" mewajibkan login.
        foreach (['warga.index', 'warga.store', 'warga.update', 'warga.destroy'] as $nama) {
            $middleware = app('router')->getRoutes()->getByName($nama)->gatherMiddleware();
            $this->assertContains('web', $middleware, $nama);
            $this->assertContains('auth', $middleware, $nama);
        }
    }
}
