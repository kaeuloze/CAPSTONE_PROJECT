<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Tombol Keluar di sidebar meminta konfirmasi dulu (wireframe) agar kader tidak keluar karena salah tekan.
 */
class KonfirmasiKeluarTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_tombol_keluar_membuka_dialog_konfirmasi_bukan_langsung_logout(): void
    {
        $this->actingAs($this->buatKader())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Apakah Anda yakin ingin keluar dari akun ini?')
            ->assertSee("batal: 'Tidak'", false)
            ->assertSee("aksi: 'Ya'", false)
            ->assertDontSee('action="'.route('logout').'"', false);
    }

    public function test_logout_dari_dialog_konfirmasi_berhasil(): void
    {
        // Dialog mengirim form POST dengan _method=POST dan token CSRF ke route logout.
        $this->actingAs($this->buatKader())
            ->post(route('logout'), ['_method' => 'POST'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
