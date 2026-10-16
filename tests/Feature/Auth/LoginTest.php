<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Pengujian login & logout kader (Activity Diagram 2.4, SOP Capstone poin 1, 6, 7).
 */
class LoginTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private const PESAN_GAGAL = 'Username atau kata sandi salah.';

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk Sistem Posyandu');
    }

    public function test_kader_dapat_login_dan_diarahkan_ke_dashboard(): void
    {
        $kader = $this->buatKader();

        $this->post(route('login.store'), ['username' => 'kader_uji', 'password' => 'RahasiaUji123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($kader);
    }

    public function test_sesi_diganti_baru_setelah_login(): void
    {
        $this->buatKader();
        $this->get(route('login'));
        $sesiLama = session()->getId();

        $this->post(route('login.store'), ['username' => 'kader_uji', 'password' => 'RahasiaUji123']);

        $this->assertNotSame($sesiLama, session()->getId());
    }

    public function test_sandi_salah_memakai_pesan_umum(): void
    {
        $this->buatKader();

        $this->from(route('login'))
            ->post(route('login.store'), ['username' => 'kader_uji', 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => self::PESAN_GAGAL]);

        $this->assertGuest();
    }

    public function test_username_tidak_terdaftar_memakai_pesan_yang_sama(): void
    {
        $this->post(route('login.store'), ['username' => 'tidak_ada', 'password' => 'apa_saja'])
            ->assertSessionHasErrors(['username' => self::PESAN_GAGAL]);

        $this->assertGuest();
    }

    public function test_isian_wajib_dan_batas_panjang(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['username' => 'Username wajib diisi.', 'password' => 'Kata sandi wajib diisi.']);

        $this->post(route('login.store'), ['username' => str_repeat('a', 51), 'password' => str_repeat('b', 65)])
            ->assertSessionHasErrors(['username', 'password']);
    }

    public function test_terkunci_setelah_5_kali_salah_walau_sandi_benar(): void
    {
        $this->buatKader();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['username' => 'kader_uji', 'password' => 'salah']);
        }

        $this->post(route('login.store'), ['username' => 'kader_uji', 'password' => 'RahasiaUji123'])
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('Terlalu banyak percobaan masuk', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_batas_percobaan_per_alamat_ip(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->post(route('login.store'), ['username' => "orang_$i", 'password' => 'salah']);
        }

        $this->post(route('login.store'), ['username' => 'orang_21', 'password' => 'salah'])
            ->assertSessionHasErrors(['username' => 'Terlalu banyak percobaan masuk dari perangkat ini. Silakan tunggu sebentar.']);
    }

    public function test_beberapa_kader_dapat_login_bersamaan(): void
    {
        $kaderA = $this->buatKader('kader_meja1');
        $kaderB = $this->buatKader('kader_meja2');

        $this->post(route('login.store'), ['username' => 'kader_meja1', 'password' => 'RahasiaUji123']);
        $this->assertAuthenticatedAs($kaderA);

        // Perangkat lain (sesi berbeda) login dengan akun berbeda.
        $this->flushSession();
        app('auth')->forgetGuards();
        $this->post(route('login.store'), ['username' => 'kader_meja2', 'password' => 'RahasiaUji123']);
        $this->assertAuthenticatedAs($kaderB);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_kader_yang_sudah_login_tidak_perlu_ke_halaman_login(): void
    {
        $this->actingAs($this->buatKader())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_tampil_untuk_kader(): void
    {
        $this->actingAs($this->buatKader())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kader Uji');
    }

    public function test_logout_menghapus_sesi_dan_kembali_ke_login(): void
    {
        $this->actingAs($this->buatKader())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
