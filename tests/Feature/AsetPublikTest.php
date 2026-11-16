<?php

namespace Tests\Feature;

use App\Services\AsetPublik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * File JS/CSS di folder public dipanggil dengan tanda versi agar browser HP tidak memakai file lama dari cache.
 */
class AsetPublikTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_alamat_file_memakai_tanda_versi_dari_isi_file(): void
    {
        $versi = substr(md5_file(public_path('js/dashboard.js')), 0, 8);

        $this->assertSame(asset('js/dashboard.js').'?v='.$versi, AsetPublik::url('js/dashboard.js'));
    }

    public function test_file_yang_tidak_ada_tetap_menghasilkan_alamat_tanpa_versi(): void
    {
        $this->assertSame(asset('js/tidak-ada.js'), AsetPublik::url('js/tidak-ada.js'));
    }

    public function test_halaman_memanggil_file_js_dengan_tanda_versi(): void
    {
        $versi = substr(md5_file(public_path('js/dashboard.js')), 0, 8);

        $this->actingAs($this->buatKader())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('js/dashboard.js?v='.$versi, false)
            ->assertDontSee("asset('js/", false);
    }
}
