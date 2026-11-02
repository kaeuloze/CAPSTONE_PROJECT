<?php

namespace Tests\Feature\Keamanan;

use App\Models\Kader;
use App\Models\Warga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * SOP Pengumpulan Capstone poin 4: ukuran request/payload dibatasi ±10 KB (REQUEST_MAKS_KB).
 */
class BatasUkuranRequestTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private const PESAN = 'Data yang dikirim terlalu besar (maksimal 10 KB). Periksa kembali isian Anda.';

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kader = $this->buatKader();
    }

    private function dataWarga(array $ubah = []): array
    {
        return array_merge([
            'nama_warga' => 'Siti Aminah', 'nik' => '3404000000000001', 'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => '0', 'alamat' => 'Tegalyoso', 'status_kondisi' => 'normal',
        ], $ubah);
    }

    public function test_kiriman_lebih_dari_10_kb_ditolak_dan_kembali_dengan_pesan(): void
    {
        $this->actingAs($this->kader)->from(route('warga.create'))
            ->post(route('warga.store'), $this->dataWarga(['catatan' => str_repeat('a', 11 * 1024)]))
            ->assertRedirect(route('warga.create'))
            ->assertSessionHas('galat', self::PESAN)
            ->assertSessionMissing('_old_input'); // isian besar tidak disimpan di sesi

        $this->assertSame(0, Warga::count());
    }

    public function test_kiriman_normal_tetap_diterima(): void
    {
        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataWarga())
            ->assertSessionHasNoErrors()->assertSessionMissing('galat');

        $this->assertSame(1, Warga::count());
    }

    public function test_permintaan_json_dibalas_413(): void
    {
        $this->actingAs($this->kader)
            ->postJson(route('presensi.atur', [1, '3404000000000001']), ['hadir' => '1', 'isi' => str_repeat('a', 11 * 1024)])
            ->assertStatus(413)
            ->assertJson(['pesan' => self::PESAN]);
    }

    public function test_parameter_alamat_lebih_dari_10_kb_ditolak(): void
    {
        $this->actingAs($this->kader)->get(route('warga.index', ['cari' => str_repeat('a', 11 * 1024)]))
            ->assertRedirect(url('/'))
            ->assertSessionHas('galat', self::PESAN);

        $this->actingAs($this->kader)->getJson(route('pemeriksaan.status', ['cari' => str_repeat('a', 11 * 1024)]))
            ->assertStatus(413);
    }

    public function test_batas_bisa_diatur_dari_env(): void
    {
        config(['posyandu.request.maks_kb' => 1]);

        $this->actingAs($this->kader)->post(route('warga.store'), $this->dataWarga(['alamat' => str_repeat('a', 1100)]))
            ->assertSessionHas('galat', 'Data yang dikirim terlalu besar (maksimal 1 KB). Periksa kembali isian Anda.');
    }
}
