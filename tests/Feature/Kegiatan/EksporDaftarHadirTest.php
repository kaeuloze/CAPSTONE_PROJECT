<?php

namespace Tests\Feature\Kegiatan;

use App\Models\Kader;
use App\Models\Kegiatan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Ekspor daftar hadir presensi ke Excel + halaman siap cetak (permintaan kader 31 Okt 2026):
 * hanya warga Hadir, urut abjad, kolom No, Nama, NIK, Kategori, Jam Hadir, Tanda Tangan (kosong).
 */
class EksporDaftarHadirTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 08:00:00'));
        $this->kader = $this->buatKader();
        $this->kegiatan = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Oktober 2026', 'tanggal_kegiatan' => '2026-10-10',
            'status_kegiatan' => true, 'waktu_dibuka' => now(),
        ]);

        // Hadir: Zaenal (dewasa, 08:05), Ani (ibu hamil, 08:20), Budi (balita, 08:10); tidak hadir: Citra & Dodi.
        $this->hadir('3404000000000001', 'Zaenal Dewasa', [], '08:05:00');
        $this->hadir('3404000000000002', 'Ani Hamil', ['status_kondisi' => 'hamil'], '08:20:00');
        $this->hadir('3404000000000003', 'Budi Balita', ['tanggal_lahir' => '2023-01-01'], '08:10:00');
        $this->buatWarga('3404000000000004', ['nama_warga' => 'Citra Belum Hadir']);
        $this->kegiatan->kehadiran()->create(['nik' => $this->buatWarga('3404000000000005', ['nama_warga' => 'Dodi Batal'])->nik, 'status_kehadiran' => false, 'waktu_kehadiran' => now()]);
    }

    private function hadir(string $nik, string $nama, array $ubah, string $jam): void
    {
        $this->buatWarga($nik, ['nama_warga' => $nama] + $ubah);
        $this->kegiatan->kehadiran()->create(['nik' => $nik, 'status_kehadiran' => true, 'waktu_kehadiran' => "2026-10-10 {$jam}"]);
    }

    private function unduh(): Worksheet
    {
        $respon = $this->actingAs($this->kader)->get(route('presensi.excel', $this->kegiatan));
        $respon->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('daftar-hadir-2026-10-10-'.$this->kegiatan->id_kegiatan.'.xlsx', $respon->headers->get('Content-Disposition'));

        $berkas = tempnam(sys_get_temp_dir(), 'hadir').'.xlsx';
        file_put_contents($berkas, $respon->streamedContent());
        $lembar = IOFactory::load($berkas)->getActiveSheet();
        @unlink($berkas);

        return $lembar;
    }

    public function test_wajib_login(): void
    {
        $this->get(route('presensi.excel', $this->kegiatan))->assertRedirect(route('login'));
        $this->get(route('presensi.cetak', $this->kegiatan))->assertRedirect(route('login'));
    }

    public function test_tombol_ekspor_di_halaman_presensi(): void
    {
        $this->actingAs($this->kader)->get(route('presensi.index', $this->kegiatan))
            ->assertSee(route('presensi.excel', $this->kegiatan))->assertSee('Ekspor ke Excel (.xlsx)')
            ->assertSee(route('presensi.cetak', $this->kegiatan))->assertSee('Cetak Daftar Hadir')
            ->assertSee('data-cetak-langsung', false)->assertSee('js/presensi-cetak.js');
    }

    public function test_excel_hanya_warga_hadir_urut_abjad(): void
    {
        $lembar = $this->unduh();

        $this->assertSame('DAFTAR HADIR POSYANDU MAWAR 1', $lembar->getCell('A1')->getValue());
        $this->assertSame(': Posyandu Oktober 2026', $lembar->getCell('C3')->getValue());
        $this->assertSame(': Sabtu, 10 Oktober 2026', $lembar->getCell('C4')->getValue());
        $this->assertSame(': 3 warga', $lembar->getCell('C6')->getValue());
        $this->assertSame(['No', 'Nama', 'NIK', 'Kategori', 'Jam Hadir', 'Tanda Tangan'], $lembar->rangeToArray('A8:F8')[0]);
        $this->assertSame([
            [1, 'Ani Hamil', '3404000000000002', 'Ibu Hamil', '08:20', null],
            [2, 'Budi Balita', '3404000000000003', 'Balita', '08:10', null],
            [3, 'Zaenal Dewasa', '3404000000000001', 'Dewasa', '08:05', null],
        ], $lembar->rangeToArray('A9:F11', null, false, false));
        $this->assertNull($lembar->getCell('B12')->getValue()); // Citra & Dodi tidak ikut
    }

    public function test_halaman_cetak(): void
    {
        $this->actingAs($this->kader)->get(route('presensi.cetak', $this->kegiatan))
            ->assertOk()->assertSee('DAFTAR HADIR POSYANDU MAWAR 1')->assertSee('Sabtu, 10 Oktober 2026')
            ->assertSeeInOrder(['No', 'Nama', 'NIK', 'Kategori', 'Jam Hadir', 'Tanda Tangan'])
            ->assertSeeInOrder(['Ani Hamil', 'Ibu Hamil', '08:20', 'Budi Balita', 'Balita', 'Zaenal Dewasa', 'Dewasa'])
            ->assertDontSee('Citra Belum Hadir')->assertDontSee('Dodi Batal')
            ->assertSee("window.addEventListener('load', function () { window.print(); });", false) // langsung mencetak
            ->assertDontSee(route('presensi.excel', $this->kegiatan)); // tombol Excel cukup di halaman Presensi
    }

    public function test_arsip_dan_kegiatan_tanpa_warga_hadir(): void
    {
        $kosong = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu September 2026', 'tanggal_kegiatan' => '2026-09-12',
            'status_kegiatan' => false, 'waktu_dibuka' => now(),
        ]);

        $this->actingAs($this->kader)->get(route('presensi.cetak', $kosong))
            ->assertOk()->assertSee('Belum ada warga yang hadir.');
        $this->actingAs($this->kader)->get(route('presensi.excel', $kosong))->assertOk();
    }
}
