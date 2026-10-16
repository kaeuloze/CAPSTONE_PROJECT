<?php

namespace Tests\Feature\Pemeriksaan;

use App\Enums\JenisPemeriksaan;
use App\Enums\KategoriPemeriksaan;
use App\Enums\StatusPemeriksaan;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use App\Services\Pemeriksaan\Bagian;
use App\Services\Pemeriksaan\DefinisiForm;
use App\Services\Pemeriksaan\KatalogForm;
use App\Services\Pemeriksaan\PemeriksaanService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Fondasi menu Pemeriksaan: daftar antrean, halaman tanpa kegiatan aktif, pemilihan form,
 * Simpan per bagian antar-meja, dan Submit (keputusan 39). Memakai form uji sederhana
 * karena form per kategori dibuat pada tahap berikutnya.
 */
class FondasiPemeriksaanTest extends TestCase
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
            'nama_kegiatan' => 'Posyandu Oktober 2026', 'tanggal_kegiatan' => '2026-10-16',
            'status_kegiatan' => true, 'waktu_dibuka' => now(),
        ]);
        app(KatalogForm::class)->daftarkan(new FormUjiDewasa);
        View::addLocation(__DIR__.'/views'); // tampilan form uji
    }

    private function hadirkan(Warga $warga): Warga
    {
        $this->kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        return $warga;
    }

    private function versi(Warga $warga, string $bagian): string
    {
        $p = Pemeriksaan::where('nik', $warga->nik)->first();
        $form = new FormUjiDewasa;
        $b = $form->bagian()[$bagian];

        return $b->versi($p?->{$b->relasi}()->first());
    }

    private function simpan(Warga $warga, array $isian, array $versi, ?array $diubah, string $aksi = 'simpan', ?Kader $kader = null)
    {
        $data = ['bagian' => $isian, 'versi' => $versi, 'aksi' => $aksi];
        if ($diubah !== null) {
            $data['diubah'] = $diubah;
        }

        return $this->actingAs($kader ?? $this->kader)->post(route('pemeriksaan.simpan', $warga->nik), $data);
    }

    // ---------- Akses & halaman ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('pemeriksaan.index'))->assertRedirect(route('login'));
        $this->getJson(route('pemeriksaan.status'))->assertUnauthorized();
    }

    public function test_tanpa_kegiatan_aktif_menampilkan_halaman_kosong(): void
    {
        $this->kegiatan->update(['status_kegiatan' => false]);

        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))
            ->assertOk()->assertSee('Belum ada kegiatan posyandu yang aktif')->assertSee('Pergi ke Kegiatan');
    }

    public function test_daftar_hanya_warga_hadir_bukan_balita_dan_belum_submit(): void
    {
        $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani Dewasa']));
        $this->buatWarga('3404000000000002', ['nama_warga' => 'Budi Belum Hadir']);
        $this->hadirkan($this->buatWarga('3404000000000003', ['nama_warga' => 'Caca Balita', 'tanggal_lahir' => '2024-01-01']));
        $selesai = $this->hadirkan($this->buatWarga('3404000000000004', ['nama_warga' => 'Dodi Selesai']));
        $this->buatPemeriksaan($selesai, $this->kegiatan, ['status_pemeriksaan' => StatusPemeriksaan::Selesai]);
        $draft = $this->hadirkan($this->buatWarga('3404000000000005', ['nama_warga' => 'Eka Draft']));
        $this->buatPemeriksaan($draft, $this->kegiatan);

        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))
            ->assertOk()->assertSee('Kegiatan aktif: Posyandu Oktober 2026')
            ->assertSee('Ani Dewasa')->assertSee('Mulai Pemeriksaan')
            ->assertSee('Eka Draft')->assertSee('Pemeriksaan Belum Selesai')
            ->assertDontSee('Budi Belum Hadir')->assertDontSee('Caca Balita')->assertDontSee('Dodi Selesai');
    }

    public function test_kategori_form_dipilih_otomatis(): void
    {
        $layanan = app(PemeriksaanService::class);
        $tgl = $this->kegiatan->tanggal_kegiatan;

        $this->assertSame(KategoriPemeriksaan::Remaja, $layanan->kategoriUntuk($this->buatWarga('3404000000000001', ['tanggal_lahir' => '2012-01-01']), $tgl));
        $this->assertSame(KategoriPemeriksaan::Dewasa, $layanan->kategoriUntuk($this->buatWarga('3404000000000002'), $tgl));
        $this->assertSame(KategoriPemeriksaan::Lansia, $layanan->kategoriUntuk($this->buatWarga('3404000000000003', ['tanggal_lahir' => '1950-01-01']), $tgl));
        $this->assertSame(KategoriPemeriksaan::IbuHamil, $layanan->kategoriUntuk($this->buatWarga('3404000000000004', ['status_kondisi' => 'hamil']), $tgl));
        $this->assertSame(KategoriPemeriksaan::IbuNifas, $layanan->kategoriUntuk($this->buatWarga('3404000000000005', ['status_kondisi' => 'nifas']), $tgl));
        $this->assertSame(KategoriPemeriksaan::IbuNifas, $layanan->kategoriUntuk($this->buatWarga('3404000000000006', ['status_kondisi' => 'menyusui']), $tgl));
        $this->assertNull($layanan->kategoriUntuk($this->buatWarga('3404000000000007', ['tanggal_lahir' => '2024-01-01']), $tgl));
    }

    public function test_filter_kategori_dan_pencarian(): void
    {
        $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani Dewasa']));
        $this->hadirkan($this->buatWarga('3404000000000002', ['nama_warga' => 'Bunga Hamil', 'status_kondisi' => 'hamil']));

        $this->actingAs($this->kader)->get(route('pemeriksaan.index', ['kategori' => 'ibu_hamil']))
            ->assertSee('Bunga Hamil')->assertDontSee('Ani Dewasa');
        $this->actingAs($this->kader)->get(route('pemeriksaan.index', ['cari' => 'ani']))
            ->assertSee('Ani Dewasa')->assertDontSee('Bunga Hamil');
        $this->actingAs($this->kader)->get(route('pemeriksaan.index', ['kategori' => 'balita']))
            ->assertSessionHasErrors('kategori');
    }

    public function test_polling_menampilkan_warga_yang_baru_hadir(): void
    {
        $this->actingAs($this->kader)->getJson(route('pemeriksaan.status'))
            ->assertOk()->assertJson(['aktif' => true, 'total' => 0]);

        $warga = $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani Dewasa']));

        $this->actingAs($this->kader)->getJson(route('pemeriksaan.status'))
            ->assertJson(['total' => 1, 'baris' => [['nik' => $warga->nik, 'nama' => 'Ani Dewasa', 'kategori' => 'Dewasa', 'draft' => false]]]);
    }

    public function test_polling_memberi_tahu_bila_tidak_ada_kegiatan_aktif(): void
    {
        $this->kegiatan->update(['status_kegiatan' => false]);
        $this->actingAs($this->kader)->getJson(route('pemeriksaan.status'))->assertJson(['aktif' => false]);
    }

    public function test_form_terbuka_untuk_warga_hadir(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani Dewasa', 'tanggal_lahir' => '1990-01-01']));

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $warga->nik))
            ->assertOk()->assertSee('Form Uji Dewasa')->assertSee('Ani Dewasa')->assertSee('Dewasa · 36 th')
            ->assertSee('Kehadiran: Hadir')->assertSee('name="versi[ukur]"', false)
            ->assertSee('Simpan')->assertSee('Submit');

        // Membuka form saja tidak membuat data (keputusan: tercatat saat Simpan pertama).
        $this->assertSame(0, Pemeriksaan::count());
    }

    public function test_form_ditolak_untuk_warga_belum_hadir_balita_atau_sudah_submit(): void
    {
        $belum = $this->buatWarga('3404000000000001', ['nama_warga' => 'Budi']);
        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $belum->nik))
            ->assertRedirect(route('pemeriksaan.index'))->assertSessionHas('galat');

        $balita = $this->hadirkan($this->buatWarga('3404000000000002', ['tanggal_lahir' => '2024-01-01']));
        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $balita->nik))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Vaksinasi Balita'));

        $selesai = $this->hadirkan($this->buatWarga('3404000000000003'));
        $this->buatPemeriksaan($selesai, $this->kegiatan, ['status_pemeriksaan' => StatusPemeriksaan::Selesai]);
        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $selesai->nik))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Riwayat Pemeriksaan'));
    }

    public function test_kategori_tanpa_form_menampilkan_sedang_disiapkan(): void
    {
        $bunifas = $this->hadirkan($this->buatWarga('3404000000000001', ['status_kondisi' => 'nifas']));

        $this->actingAs($this->kader)->get(route('pemeriksaan.form', $bunifas->nik))
            ->assertOk()->assertSee('Form Ibu Nifas sedang disiapkan');
    }

    // ---------- Simpan ----------

    public function test_simpan_pertama_membuat_draft_lalu_kembali_ke_daftar(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani']));

        $this->simpan($warga, ['ukur' => ['berat_badan' => '55.5']], ['ukur' => $this->versi($warga, 'ukur')], ['ukur'])
            ->assertRedirect(route('pemeriksaan.index'))
            ->assertSessionHas('sukses', 'Pemeriksaan Ani tersimpan sementara (belum di-Submit).');

        $p = Pemeriksaan::sole();
        $this->assertSame(StatusPemeriksaan::Draft, $p->status_pemeriksaan);
        $this->assertSame(KategoriPemeriksaan::Dewasa, $p->kategori_usia_saat_periksa);
        $this->assertSame('2026-10-16', $p->tanggal_pemeriksaan->toDateString());
        $this->assertSame('55.50', $p->pengukuranDasar->berat_badan);
        $this->assertNull($p->penyuluhanRujukan); // bagian yang tidak diisi tidak dibuat
    }

    public function test_dua_meja_mengisi_bagian_berbeda_tidak_saling_menimpa(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));
        $kader2 = $this->buatKader('kader_meja_2');
        // Kedua meja membuka form pada saat yang sama (versi awal sama).
        $versiAwal = ['ukur' => $this->versi($warga, 'ukur'), 'penyuluhan' => $this->versi($warga, 'penyuluhan')];

        // Meja timbang mengisi pengukuran.
        $this->simpan($warga, ['ukur' => ['berat_badan' => '60', 'tinggi_badan' => '160'], 'penyuluhan' => ['topik_penyuluhan' => '']], $versiAwal, ['ukur'])
            ->assertSessionHas('sukses');
        // Meja penyuluhan mengisi topik dengan form lama (isian ukur masih kosong di layarnya).
        $this->simpan($warga, ['ukur' => ['berat_badan' => '', 'tinggi_badan' => ''], 'penyuluhan' => ['topik_penyuluhan' => 'Gizi seimbang']], $versiAwal, ['penyuluhan'], 'simpan', $kader2)
            ->assertSessionHas('sukses');

        $p = Pemeriksaan::sole();
        $this->assertSame('60.00', $p->pengukuranDasar->berat_badan); // tidak tertimpa kosong
        $this->assertSame('Gizi seimbang', $p->penyuluhanRujukan->topik_penyuluhan);
    }

    public function test_bagian_yang_sama_diubah_bersamaan_ditolak(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));
        $versiAwal = ['ukur' => $this->versi($warga, 'ukur')];

        $this->simpan($warga, ['ukur' => ['berat_badan' => '60']], $versiAwal, ['ukur'])->assertSessionHas('sukses');
        $this->actingAs($this->kader)->from(route('pemeriksaan.form', $warga->nik));
        $this->simpan($warga, ['ukur' => ['berat_badan' => '61']], $versiAwal, ['ukur'], 'simpan', $this->buatKader('kader_meja_2'))
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Bagian "Penimbangan Uji" baru saja diubah oleh kader lain'));

        $this->assertSame('60.00', Pemeriksaan::sole()->pengukuranDasar->berat_badan);
    }

    public function test_tanpa_javascript_bagian_yang_tidak_berubah_tidak_dianggap_konflik(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));
        $versiAwal = ['ukur' => $this->versi($warga, 'ukur'), 'penyuluhan' => $this->versi($warga, 'penyuluhan')];
        $this->simpan($warga, ['ukur' => ['berat_badan' => '60']], $versiAwal, ['ukur']);

        // Kirim ulang seluruh isian (tanpa daftar "diubah") dengan nilai ukur yang sama persis dengan database.
        $this->simpan($warga, ['ukur' => ['berat_badan' => '60.0'], 'penyuluhan' => ['topik_penyuluhan' => 'PHBS']], $versiAwal, null)
            ->assertSessionHas('sukses');

        $this->assertSame('PHBS', Pemeriksaan::sole()->penyuluhanRujukan->topik_penyuluhan);
    }

    public function test_validasi_isian(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));

        $this->simpan($warga, ['ukur' => ['berat_badan' => 'abc']], [], ['ukur'])->assertSessionHasErrors('bagian.ukur.berat_badan');
        $this->simpan($warga, ['ukur' => ['berat_badan' => '999']], [], ['ukur'])->assertSessionHasErrors('bagian.ukur.berat_badan');
        $this->simpan($warga, [], [], ['bukan_bagian'])->assertSessionHasErrors('diubah.0');
        $this->actingAs($this->kader)->post(route('pemeriksaan.simpan', $warga->nik), ['aksi' => 'hapus'])->assertSessionHasErrors('aksi');
        $this->assertSame(0, Pemeriksaan::count());
    }

    // ---------- Submit ----------

    public function test_submit_ditolak_bila_isian_wajib_kosong(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));

        $this->simpan($warga, ['ukur' => ['berat_badan' => '60']], ['ukur' => $this->versi($warga, 'ukur')], ['ukur'], 'submit')
            ->assertSessionHasErrors(['submit', 'bagian.ukur.tinggi_badan']);

        $this->assertSame(0, Pemeriksaan::count()); // dibatalkan seluruhnya; isian tetap ada di form (withInput)
    }

    public function test_submit_memakai_isian_meja_lain_lalu_hilang_dari_daftar(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001', ['nama_warga' => 'Ani']));
        $this->simpan($warga, ['ukur' => ['tinggi_badan' => '160']], ['ukur' => $this->versi($warga, 'ukur')], ['ukur']); // meja 1

        $this->simpan($warga, ['ukur' => ['berat_badan' => '60', 'tinggi_badan' => '160']], ['ukur' => $this->versi($warga, 'ukur')], ['ukur'], 'submit')
            ->assertRedirect(route('pemeriksaan.index'))
            ->assertSessionHas('sukses', 'Pemeriksaan Ani berhasil di-Submit dan masuk ke Riwayat Pemeriksaan.');

        $p = Pemeriksaan::sole();
        $this->assertSame(StatusPemeriksaan::Selesai, $p->status_pemeriksaan);
        $this->assertEquals([JenisPemeriksaan::Rutin], $p->jenis_pemeriksaan);
        $this->assertSame('23.4', $p->pengukuranDasar->getRawOriginal('kadar_hb')); // kolom hasil hitung (uji)

        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))->assertDontSee(route('pemeriksaan.form', $warga->nik));
    }

    public function test_jenis_pemeriksaan_sesuai_bagian_yang_terisi(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));

        $this->simpan($warga, ['ukur' => ['berat_badan' => '60', 'tinggi_badan' => '160'], 'penyuluhan' => ['topik_penyuluhan' => 'PHBS']],
            ['ukur' => $this->versi($warga, 'ukur'), 'penyuluhan' => $this->versi($warga, 'penyuluhan')], ['ukur', 'penyuluhan'], 'submit')
            ->assertSessionHas('sukses');

        $this->assertEqualsCanonicalizing([JenisPemeriksaan::Rutin, JenisPemeriksaan::Tahunan], Pemeriksaan::sole()->jenis_pemeriksaan);
    }

    public function test_simpan_ditolak_setelah_submit_atau_kegiatan_selesai(): void
    {
        $warga = $this->hadirkan($this->buatWarga('3404000000000001'));
        $this->buatPemeriksaan($warga, $this->kegiatan, ['status_pemeriksaan' => StatusPemeriksaan::Selesai]);
        $this->simpan($warga, ['ukur' => ['berat_badan' => '60']], [], ['ukur'])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'sudah di-Submit'));

        $lain = $this->hadirkan($this->buatWarga('3404000000000002'));
        $this->kegiatan->update(['status_kegiatan' => false]);
        $this->simpan($lain, ['ukur' => ['berat_badan' => '60']], [], ['ukur'])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'Kegiatan sudah selesai'));
    }

    public function test_menu_pemeriksaan_aktif_di_sidebar(): void
    {
        $this->actingAs($this->kader)->get(route('pemeriksaan.index'))
            ->assertSee('href="'.route('pemeriksaan.index').'"', false);
    }
}

/** Form uji sederhana untuk menguji mesin penyimpanan (bukan form sungguhan). */
class FormUjiDewasa extends DefinisiForm
{
    public function kategori(): KategoriPemeriksaan
    {
        return KategoriPemeriksaan::Dewasa;
    }

    public function judul(): string
    {
        return 'Form Uji Dewasa';
    }

    public function tab(): array
    {
        return [
            'rutin' => ['label' => 'Rutin Bulanan', 'bagian' => ['ukur']],
            'tahunan' => ['label' => 'Tahunan', 'bagian' => ['penyuluhan']],
        ];
    }

    protected function daftarBagian(): array
    {
        return [
            new Bagian(
                kunci: 'ukur',
                judul: 'Penimbangan Uji',
                relasi: 'pengukuranDasar',
                aturan: [
                    'berat_badan' => ['nullable', 'numeric', 'between:1,300'],
                    'tinggi_badan' => ['nullable', 'numeric', 'between:30,250'],
                ],
                wajib: ['berat_badan', 'tinggi_badan'],
                hitung: fn (array $n) => ['kadar_hb' => $n['berat_badan'] && $n['tinggi_badan']
                    ? round($n['berat_badan'] / (($n['tinggi_badan'] / 100) ** 2), 1) : null],
            ),
            new Bagian(
                kunci: 'penyuluhan',
                judul: 'Penyuluhan Uji',
                relasi: 'penyuluhanRujukan',
                aturan: ['topik_penyuluhan' => ['nullable', 'string', 'max:255']],
                jenis: JenisPemeriksaan::Tahunan,
            ),
        ];
    }

    public function view(): string
    {
        return 'uji';
    }
}
