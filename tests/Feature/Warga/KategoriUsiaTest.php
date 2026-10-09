<?php

namespace Tests\Feature\Warga;

use App\Enums\KategoriUsia;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Penentuan kategori usia otomatis (SKF-05). Batas disetujui: Balita < 5, Remaja 5–17,
 * Dewasa 18–59, Lansia >= 60.
 */
class KategoriUsiaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public static function batasUsia(): array
    {
        // acuan 10-10-2026
        return [
            'baru lahir' => ['2026-10-10', KategoriUsia::Balita],
            'sehari sebelum 5 tahun' => ['2021-10-11', KategoriUsia::Balita],
            'tepat 5 tahun' => ['2021-10-10', KategoriUsia::Remaja],
            'usia 17' => ['2008-10-11', KategoriUsia::Remaja],
            'tepat 18 tahun' => ['2008-10-10', KategoriUsia::Dewasa],
            'usia 59' => ['1966-10-11', KategoriUsia::Dewasa],
            'tepat 60 tahun' => ['1966-10-10', KategoriUsia::Lansia],
            'usia 90' => ['1936-01-01', KategoriUsia::Lansia],
        ];
    }

    #[DataProvider('batasUsia')]
    public function test_kategori_sesuai_batas_usia(string $tanggalLahir, KategoriUsia $harapan): void
    {
        $acuan = CarbonImmutable::parse('2026-10-10');

        $this->assertSame($harapan, KategoriUsia::dariTanggalLahir($tanggalLahir, $acuan));
    }

    public function test_lahir_29_februari_bertambah_usia_pada_1_maret(): void
    {
        $this->assertSame(KategoriUsia::Balita, KategoriUsia::dariTanggalLahir('2024-02-29', CarbonImmutable::parse('2029-02-28')));
        $this->assertSame(KategoriUsia::Remaja, KategoriUsia::dariTanggalLahir('2024-02-29', CarbonImmutable::parse('2029-03-01')));
    }

    public function test_filter_database_cocok_dengan_perhitungan_badge(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00'));
        foreach (self::batasUsia() as $nama => [$tanggal, $kategori]) {
            $this->buatWarga(str_pad((string) crc32($nama), 16, '1', STR_PAD_LEFT), ['tanggal_lahir' => $tanggal]);
        }

        foreach (KategoriUsia::cases() as $kategori) {
            $hasil = \App\Models\Warga::query()->kategori($kategori)->get();
            $this->assertNotEmpty($hasil);
            foreach ($hasil as $warga) {
                $this->assertSame($kategori, $warga->kategori_usia_terkini, $warga->tanggal_lahir->toDateString());
            }
        }
        $this->assertSame(8, \App\Models\Warga::query()->kategori(KategoriUsia::Balita)->count()
            + \App\Models\Warga::query()->kategori(KategoriUsia::Remaja)->count()
            + \App\Models\Warga::query()->kategori(KategoriUsia::Dewasa)->count()
            + \App\Models\Warga::query()->kategori(KategoriUsia::Lansia)->count());
    }

    public function test_kategori_tersimpan_otomatis_saat_tambah_dan_saat_tanggal_lahir_diubah(): void
    {
        $warga = $this->buatWarga('3404000000000099', ['tanggal_lahir' => now()->subYears(2)->toDateString(), 'kategori_usia' => 'lansia']);
        $this->assertSame(KategoriUsia::Balita, $warga->fresh()->kategori_usia);

        $warga->update(['tanggal_lahir' => now()->subYears(70)->toDateString()]);
        $this->assertSame(KategoriUsia::Lansia, $warga->fresh()->kategori_usia);
    }

    public function test_perintah_harian_memperbarui_kategori_tanpa_mengubah_updated_at(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00'));
        $warga = $this->buatWarga('3404000000000099', ['tanggal_lahir' => '2021-10-11']); // besok berusia 5 tahun
        $this->assertSame(KategoriUsia::Balita, $warga->fresh()->kategori_usia);
        $updatedAtLama = $warga->fresh()->updated_at->toDateTimeString();

        $this->travelTo(CarbonImmutable::parse('2026-10-11 00:05'));
        $this->artisan('warga:perbarui-kategori')
            ->expectsOutput('Kategori usia diperbarui untuk 1 warga.')
            ->assertSuccessful();

        $segar = $warga->fresh();
        $this->assertSame(KategoriUsia::Remaja, $segar->kategori_usia);
        $this->assertSame($updatedAtLama, $segar->updated_at->toDateTimeString());
    }

    public function test_perintah_harian_terjadwal(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('warga:perbarui-kategori')->assertSuccessful();
    }
}
