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
 * Ekspor daftar hadir presensi ke Excel + halaman siap cetak (permintaan kader 31 Okt 2026, hasil uji coba
 * bersama kader 8 Nov 2026): hanya warga Hadir, urut abjad, kolom No, Nama, NIK (4 digit terakhir), Kategori,
 * jumlah hadir di bawah tabel.
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

    public function test_excel_hanya_warga_hadir_urut_abjad_dengan_nik_4_digit_dan_jumlah(): void
    {
        $lembar = $this->unduh();

        $this->assertSame('DAFTAR HADIR POSYANDU MAWAR 1', $lembar->getCell('A1')->getValue());
        $this->assertSame(': Posyandu Oktober 2026', $lembar->getCell('C3')->getValue());
        $this->assertSame(': Sabtu, 10 Oktober 2026', $lembar->getCell('C4')->getValue());
        $this->assertSame(['No', 'Nama', 'NIK (4 digit terakhir)', 'Kategori'], $lembar->rangeToArray('A7:D7')[0]);
        $this->assertNull($lembar->getCell('E7')->getValue()); // tanpa kolom Hadir, jam hadir, tanda tangan
        $this->assertSame([
            [1, 'Ani Hamil', '0002', 'Ibu Hamil'],
            [2, 'Budi Balita', '0003', 'Balita'],
            [3, 'Zaenal Dewasa', '0001', 'Dewasa'],
        ], $lembar->rangeToArray('A8:D10', null, false, false));
        // Warga yang belum ditandai / dibatalkan tidak ikut.
        $this->assertNull($lembar->getCell('B11')->getValue());
        $this->assertSame([['Total hadir', 3]], $lembar->rangeToArray('C12:D12', null, false, false));
    }

    public function test_halaman_cetak_sama_dengan_excel(): void
    {
        $this->actingAs($this->kader)->get(route('presensi.cetak', $this->kegiatan))
            ->assertOk()->assertSee('DAFTAR HADIR POSYANDU MAWAR 1')->assertSee('Sabtu, 10 Oktober 2026')
            ->assertSeeInOrder(['No', 'Nama', 'NIK (4 digit terakhir)', 'Kategori'])
            ->assertSeeInOrder(['Ani Hamil', '0002', 'Ibu Hamil', 'Budi Balita', '0003', 'Balita', 'Zaenal Dewasa', '0001', 'Dewasa'])
            ->assertSeeInOrder(['Total hadir', ': 3 warga'])
            ->assertDontSee('Citra Belum Hadir')->assertDontSee('Dodi Batal')->assertDontSee('Tidak Hadir')
            ->assertDontSee('3404000000000002') // NIK lengkap tidak tampil di kertas cetak
            ->assertDontSee('Jam Hadir')->assertDontSee('Tanda Tangan')
            ->assertSee("window.addEventListener('load', function () { window.print(); });", false) // langsung mencetak
            ->assertDontSee(route('presensi.excel', $this->kegiatan)); // tombol Excel cukup di halaman Presensi
    }

    public function test_arsip_hanya_warga_hadir_dan_kegiatan_kosong(): void
    {
        $arsip = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu September 2026', 'tanggal_kegiatan' => '2026-09-12',
            'status_kegiatan' => false, 'waktu_dibuka' => now(),
        ]);
        $arsip->kehadiran()->create(['nik' => '3404000000000003', 'status_kehadiran' => true, 'waktu_kehadiran' => '2026-09-12 08:00:00']);
        $arsip->kehadiran()->create(['nik' => '3404000000000004', 'status_kehadiran' => false, 'waktu_kehadiran' => '2026-09-12 08:00:00']);

        // Arsip: hanya warga yang Hadir pada kegiatan itu.
        $this->actingAs($this->kader)->get(route('presensi.cetak', $arsip))
            ->assertOk()->assertSee('Budi Balita')->assertDontSee('Citra Belum Hadir')
            ->assertDontSee('Zaenal Dewasa')->assertSeeInOrder(['Total hadir', ': 1 warga']);

        $kosong = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Agustus 2026', 'tanggal_kegiatan' => '2026-08-12',
            'status_kegiatan' => false, 'waktu_dibuka' => now(),
        ]);
        $this->actingAs($this->kader)->get(route('presensi.cetak', $kosong))
            ->assertOk()->assertSee('Belum ada warga yang hadir.');
        $this->actingAs($this->kader)->get(route('presensi.excel', $kosong))->assertOk();
    }
}
