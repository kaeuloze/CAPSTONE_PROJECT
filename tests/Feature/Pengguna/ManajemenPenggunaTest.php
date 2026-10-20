<?php

namespace Tests\Feature\Pengguna;

use App\Models\Kader;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Manajemen Pengguna / akun kader (SKF-11; Figma 141:164 & 141:2478; Activity Diagram 2.10–2.13).
 */
class ManajemenPenggunaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 08:00:00'));
        $this->kader = $this->buatKader('yuli', 'RahasiaUji123');
        $this->kader->update(['nama_kader' => 'Ibu Yuli']);
    }

    private function dataBaru(array $ubah = []): array
    {
        return array_merge([
            'nama_kader' => 'Ibu Sari',
            'username' => 'sari',
            'password' => 'kopi pagi di posyandu',
            'password_confirmation' => 'kopi pagi di posyandu',
        ], $ubah);
    }

    private function versi(Kader $kader): string
    {
        return $kader->refresh()->updated_at->format('Y-m-d H:i:s');
    }

    private function buatSesi(Kader $kader, string $id): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $kader->id_kader, 'ip_address' => '127.0.0.1',
            'user_agent' => 'uji', 'payload' => '', 'last_activity' => now()->timestamp]);
    }

    public function test_wajib_login_dan_menu_aktif(): void
    {
        $this->get(route('pengguna.index'))->assertRedirect(route('login'));
        $this->get(route('pengguna.create'))->assertRedirect(route('login'));

        $html = $this->actingAs($this->kader)->get(route('pengguna.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"\s*><i aria-hidden="true"><\/i>Manajemen Pengguna/', preg_replace('/\s+/', ' ', $html));
    }

    public function test_daftar_akun_dan_pencarian(): void
    {
        $this->buatKader('rina')->update(['nama_kader' => 'Ibu Rina']);
        $this->buatKader('budi_s')->update(['nama_kader' => 'Pak Budi']);

        $this->actingAs($this->kader)->get(route('pengguna.index'))
            ->assertOk()->assertSee('Kelola Data Akun')->assertSee('+ Tambah Akun')
            ->assertSeeInOrder(['Nama Kader', 'Username', 'Aksi'])
            ->assertSeeInOrder(['Ibu Rina', 'rina', 'Ibu Yuli', 'Akun Anda', 'yuli', 'Pak Budi', 'budi_s'])
            ->assertDontSee('password_hash')->assertDontSee('$2y$', false);

        $this->actingAs($this->kader)->get(route('pengguna.index', ['cari' => 'budi']))
            ->assertSee('Pak Budi')->assertDontSee('Ibu Rina');
        $this->actingAs($this->kader)->get(route('pengguna.index', ['cari' => 'Rina']))
            ->assertSee('Ibu Rina')->assertDontSee('Pak Budi');
        $this->actingAs($this->kader)->get(route('pengguna.index', ['cari' => '_']))
            ->assertSee('Pak Budi')->assertDontSee('Ibu Rina'); // _ bukan wildcard
        $this->actingAs($this->kader)->get(route('pengguna.index', ['cari' => 'zzz']))
            ->assertSee('Tidak ada akun yang cocok dengan pencarian.');
    }

    public function test_daftar_memakai_halaman(): void
    {
        foreach (range(1, 16) as $i) {
            $this->buatKader(sprintf('akun%02d', $i))->update(['nama_kader' => sprintf('Kader %02d', $i)]);
        }

        // Urut nama: Ibu Yuli, Kader 01 … Kader 16 → 15 akun per halaman.
        $this->actingAs($this->kader)->get(route('pengguna.index'))->assertSee('Ibu Yuli')->assertSee('Kader 14')->assertDontSee('Kader 15');
        $this->actingAs($this->kader)->get(route('pengguna.index', ['page' => 2]))->assertSee('Kader 15')->assertSee('Kader 16')->assertDontSee('Kader 14');
    }

    public function test_tambah_akun_lalu_bisa_login(): void
    {
        $this->actingAs($this->kader)->get(route('pengguna.create'))->assertOk()
            ->assertSee('Tambah / Edit Akun Kader')->assertSee('Data Akun Petugas')
            ->assertSeeInOrder(['Nama kader', 'Username', 'Kata sandi', 'Konfirmasi kata sandi', 'Simpan Akun', 'Batal']);

        $this->actingAs($this->kader)->post(route('pengguna.store'), $this->dataBaru(['nama_kader' => '  Ibu   Sari ', 'username' => ' Sari ']))
            ->assertRedirect(route('pengguna.index'))->assertSessionHas('sukses', 'Akun Ibu Sari berhasil dibuat.');

        $akun = Kader::where('username', 'sari')->firstOrFail();
        $this->assertSame('Ibu Sari', $akun->nama_kader);
        $this->assertNotSame('kopi pagi di posyandu', $akun->getAuthPassword());
        $this->assertTrue(Hash::check('kopi pagi di posyandu', $akun->getAuthPassword()));

        auth()->logout();
        $this->post(route('login.store'), ['username' => 'sari', 'password' => 'kopi pagi di posyandu'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($akun);
    }

    public function test_validasi_tambah_akun(): void
    {
        $this->actingAs($this->kader);

        $this->post(route('pengguna.store'), [])->assertSessionHasErrors([
            'nama_kader' => 'Nama kader wajib diisi.', 'username' => 'Username wajib diisi.', 'password' => 'Kata sandi wajib diisi.',
        ]);
        $this->post(route('pengguna.store'), $this->dataBaru(['username' => 'YULI']))
            ->assertSessionHasErrors(['username' => 'Username ini sudah dipakai akun lain.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['username' => 'ibu sari']))
            ->assertSessionHasErrors(['username' => 'Username hanya boleh berisi huruf kecil, angka, titik (.), dan garis bawah (_), tanpa spasi.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['username' => 'ab']))->assertSessionHasErrors(['username' => 'Username minimal 3 karakter.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['username' => str_repeat('a', 51)]))->assertSessionHasErrors('username');
        $this->post(route('pengguna.store'), $this->dataBaru(['nama_kader' => '<script>']))->assertSessionHasErrors('nama_kader');
        $this->post(route('pengguna.store'), $this->dataBaru(['nama_kader' => str_repeat('a', 101)]))->assertSessionHasErrors('nama_kader');

        // Kata sandi: panjang, konfirmasi, sama dengan username, sandi umum.
        $this->post(route('pengguna.store'), $this->dataBaru(['password' => 'abc1234', 'password_confirmation' => 'abc1234']))
            ->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
        $panjang = str_repeat('a', 65);
        $this->post(route('pengguna.store'), $this->dataBaru(['password' => $panjang, 'password_confirmation' => $panjang]))
            ->assertSessionHasErrors(['password' => 'Kata sandi maksimal 64 karakter.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['password_confirmation' => 'berbeda sekali']))
            ->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi tidak sama dengan kata sandi.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['username' => 'sari.mawar', 'password' => 'Sari.Mawar', 'password_confirmation' => 'Sari.Mawar']))
            ->assertSessionHasErrors(['password' => 'Kata sandi tidak boleh sama dengan username.']);
        $this->post(route('pengguna.store'), $this->dataBaru(['password' => 'Posyandu1', 'password_confirmation' => 'Posyandu1']))
            ->assertSessionHasErrors(['password' => 'Kata sandi ini terlalu umum dan mudah ditebak. Gunakan kata sandi lain.']);

        // Tanpa paksaan komposisi: huruf kecil saja (8 karakter) diterima.
        $this->post(route('pengguna.store'), $this->dataBaru(['password' => 'melatiku', 'password_confirmation' => 'melatiku']))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Kader::count());
    }

    public function test_kata_sandi_tidak_ikut_dikembalikan_saat_gagal(): void
    {
        $this->actingAs($this->kader)->from(route('pengguna.create'))->followingRedirects()
            ->post(route('pengguna.store'), $this->dataBaru(['username' => 'ab', 'password' => 'rahasia sekali', 'password_confirmation' => 'rahasia sekali']))
            ->assertSee('Akun belum bisa disimpan')->assertSee('value="ab"', false)->assertDontSee('rahasia sekali');
    }

    public function test_sandi_bocor_ditolak_dan_dilewati_bila_layanan_tidak_terjangkau(): void
    {
        config(['posyandu.pengguna.cek_sandi_bocor' => true]);
        $sandi = 'sandi yang bocor';
        $hash = strtoupper(sha1($sandi));
        Http::fake(['api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).":42\r\nABCDEF:1")]);

        $this->actingAs($this->kader)->post(route('pengguna.store'), $this->dataBaru(['password' => $sandi, 'password_confirmation' => $sandi]))
            ->assertSessionHasErrors(['password' => 'Kata sandi ini pernah bocor di internet sehingga mudah ditebak. Gunakan kata sandi lain.']);
        Http::assertSent(fn ($r) => ! str_contains($r->url(), $hash) && str_ends_with($r->url(), substr($hash, 0, 5)));

        // Layanan tidak bisa dihubungi → pengecekan dilewati, akun tetap bisa dibuat.
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 503)]);
        $this->actingAs($this->kader)->post(route('pengguna.store'), $this->dataBaru(['password' => 'sandi aman sekali', 'password_confirmation' => 'sandi aman sekali']))
            ->assertSessionHasNoErrors()->assertRedirect(route('pengguna.index'));
    }

    public function test_edit_tanpa_mengubah_kata_sandi(): void
    {
        $sari = $this->buatKader('sari', 'SandiLamaSari1');

        $this->actingAs($this->kader)->get(route('pengguna.edit', $sari))->assertOk()
            ->assertSee('value="sari"', false)->assertSee('Kosongkan bila kata sandi tidak diubah.');

        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), [
            'nama_kader' => 'Ibu Sari Wulandari', 'username' => 'sari.w', 'password' => '', 'password_confirmation' => '', 'versi' => $this->versi($sari),
        ])->assertRedirect(route('pengguna.index'))->assertSessionHas('sukses', 'Perubahan akun Ibu Sari Wulandari berhasil disimpan.');

        $sari->refresh();
        $this->assertSame(['Ibu Sari Wulandari', 'sari.w'], [$sari->nama_kader, $sari->username]);
        $this->assertTrue(Hash::check('SandiLamaSari1', $sari->getAuthPassword()));
    }

    public function test_edit_kata_sandi_harus_dikonfirmasi(): void
    {
        $sari = $this->buatKader('sari', 'SandiLamaSari1');
        $data = ['nama_kader' => 'Ibu Sari', 'username' => 'sari', 'versi' => $this->versi($sari)];

        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), $data + ['password' => 'sandi baru sari'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi tidak sama dengan kata sandi.']);
        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), array_merge($data, ['username' => 'yuli', 'password' => '']))
            ->assertSessionHasErrors(['username' => 'Username ini sudah dipakai akun lain.']);
        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), ['nama_kader' => 'Ibu Sari', 'username' => 'sari'])
            ->assertSessionHasErrors('versi');

        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), $data + ['password' => 'sandi baru sari', 'password_confirmation' => 'sandi baru sari'])
            ->assertSessionHas('sukses', 'Perubahan akun Ibu Sari berhasil disimpan. Akun ini perlu login ulang dengan kata sandi baru.');
        $this->assertTrue(Hash::check('sandi baru sari', $sari->refresh()->getAuthPassword()));
    }

    public function test_edit_bersamaan_ditolak(): void
    {
        $sari = $this->buatKader('sari');
        $versiLama = $this->versi($sari);

        // Kader lain menyimpan lebih dulu (namanya tetap sama pun versi tetap berganti).
        $this->travel(1)->minutes();
        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), ['nama_kader' => 'Kader Uji', 'username' => 'sari', 'versi' => $versiLama])
            ->assertSessionHas('sukses');

        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), ['nama_kader' => 'Tertimpa', 'username' => 'sari', 'versi' => $versiLama])
            ->assertSessionHas('galat', fn ($p) => str_contains($p, 'baru saja diubah'));
        $this->assertSame('Kader Uji', $sari->refresh()->nama_kader);
    }

    public function test_ganti_sandi_mengeluarkan_sesi_akun_itu(): void
    {
        config(['session.driver' => 'database']);
        $sari = $this->buatKader('sari');
        $this->buatSesi($sari, 'sesi-sari-hp');
        $this->buatSesi($sari, 'sesi-sari-laptop');
        $this->buatSesi($this->kader, 'sesi-yuli-lain');

        // Ganti nama saja → sesi tetap.
        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), ['nama_kader' => 'Ibu Sari', 'username' => 'sari', 'versi' => $this->versi($sari)]);
        $this->assertSame(2, DB::table('sessions')->where('user_id', $sari->id_kader)->count());

        $this->travel(1)->minutes();
        $this->actingAs($this->kader)->put(route('pengguna.update', $sari), [
            'nama_kader' => 'Ibu Sari', 'username' => 'sari', 'password' => 'sandi baru sari', 'password_confirmation' => 'sandi baru sari', 'versi' => $this->versi($sari),
        ])->assertSessionHas('sukses');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $sari->id_kader)->count());
        $this->assertTrue(DB::table('sessions')->where('id', 'sesi-yuli-lain')->exists()); // akun lain tidak terganggu
    }

    public function test_ganti_sandi_sendiri_tetap_login_dan_perangkat_lain_keluar(): void
    {
        config(['session.driver' => 'database']);
        $this->buatSesi($this->kader, 'sesi-yuli-hp');

        $this->actingAs($this->kader)->put(route('pengguna.update', $this->kader), [
            'nama_kader' => 'Ibu Yuli', 'username' => 'yuli', 'password' => 'sandi baru yuli', 'password_confirmation' => 'sandi baru yuli', 'versi' => $this->versi($this->kader),
        ])->assertRedirect(route('pengguna.index'))
            ->assertSessionHas('sukses', 'Perubahan akun Ibu Yuli berhasil disimpan. Perangkat lain yang memakai akun ini sudah dikeluarkan.');

        $this->assertAuthenticatedAs($this->kader);
        $this->assertFalse(DB::table('sessions')->where('id', 'sesi-yuli-hp')->exists());
    }

    public function test_hapus_akun(): void
    {
        config(['session.driver' => 'database']);
        $sari = $this->buatKader('sari');
        $this->buatSesi($sari, 'sesi-sari-hp');

        $this->actingAs($this->kader)->get(route('pengguna.index'))->assertSee('Yakin hapus akun ini?');
        $this->actingAs($this->kader)->delete(route('pengguna.destroy', $sari))
            ->assertRedirect(route('pengguna.index'))->assertSessionHas('sukses', 'Akun Kader Uji berhasil dihapus.');
        $this->assertModelMissing($sari);
        $this->assertFalse(DB::table('sessions')->where('id', 'sesi-sari-hp')->exists());

        // Akun yang sudah dihapus (mis. oleh kader lain) tidak ditemukan lagi.
        $this->actingAs($this->kader)->delete(route('pengguna.destroy', $sari->id_kader))->assertNotFound();
    }

    public function test_akun_terakhir_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->kader)->delete(route('pengguna.destroy', $this->kader))
            ->assertSessionHas('galat', 'Akun ini tidak bisa dihapus karena merupakan satu-satunya akun. Tambahkan akun lain terlebih dahulu.');
        $this->assertModelExists($this->kader);
        $this->assertAuthenticatedAs($this->kader);
    }

    public function test_hapus_akun_sendiri_lalu_keluar(): void
    {
        $this->buatKader('sari');

        $this->actingAs($this->kader)->delete(route('pengguna.destroy', $this->kader))->assertRedirect(route('login'));
        $this->assertModelMissing($this->kader);
        $this->assertGuest();
    }
}
