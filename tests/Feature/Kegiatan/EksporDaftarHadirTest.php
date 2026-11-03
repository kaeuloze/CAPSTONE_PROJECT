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
 * Ekspor daftar hadir presensi ke Excel + halaman siap cetak (permintaan kader 31 Okt 2026, koreksi tim 4 Nov):
 * semua warga Hadir & Tidak Hadir, urut abjad, kolom No, Nama, NIK, Kategori, Hadir / Tidak Hadir,
 * total Hadir / Tidak Hadir di bawah tabel.
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

    public function test_excel_semua_warga_hadir_dan_tidak_hadir_urut_abjad_dengan_total(): void
    {
        $lembar = $this->unduh();

        $this->assertSame('DAFTAR HADIR POSYANDU MAWAR 1', $lembar->getCell('A1')->getValue());
        $this->assertSame(': Posyandu Oktober 2026', $lembar->getCell('C3')->getValue());
        $this->assertSame(': Sabtu, 10 Oktober 2026', $lembar->getCell('C4')->getValue());
        $this->assertSame(['No', 'Nama', 'NIK', 'Kategori', 'Hadir / Tidak Hadir'], $lembar->rangeToArray('A7:E7')[0]);
        $this->assertNull($lembar->getCell('F7')->getValue()); // tanpa jam hadir & tanda tangan
        $this->assertSame([
            [1, 'Ani Hamil', '3404000000000002', 'Ibu Hamil', 'Hadir'],
            [2, 'Budi Balita', '3404000000000003', 'Balita', 'Hadir'],
            [3, 'Citra Belum Hadir', '3404000000000004', 'Dewasa', 'Tidak Hadir'], // belum ditandai saat berlangsung
            [4, 'Dodi Batal', '3404000000000005', 'Dewasa', 'Tidak Hadir'],
            [5, 'Zaenal Dewasa', '3404000000000001', 'Dewasa', 'Hadir'],
        ], $lembar->rangeToArray('A8:E12', null, false, false));
        // Total di bawah tabel.
        $this->assertSame([['Hadir', 3], ['Tidak Hadir', 2], ['Total warga', 5]], $lembar->rangeToArray('D14:E16', null, false, false));
    }

    public function test_halaman_cetak(): void
    {
        $this->actingAs($this->kader)->get(route('presensi.cetak', $this->kegiatan))
            ->assertOk()->assertSee('DAFTAR HADIR POSYANDU MAWAR 1')->assertSee('Sabtu, 10 Oktober 2026')
            ->assertSeeInOrder(['No', 'Nama', 'NIK', 'Kategori', 'Hadir / Tidak Hadir'])
            ->assertSeeInOrder(['Ani Hamil', 'Hadir', 'Budi Balita', 'Hadir', 'Citra Belum Hadir', 'Tidak Hadir',
                'Dodi Batal', 'Tidak Hadir', 'Zaenal Dewasa', 'Hadir'])
            ->assertSeeInOrder(['Hadir', ': 3 warga', 'Tidak Hadir', ': 2 warga', 'Total warga', ': 5 warga'])
            ->assertDontSee('Jam Hadir')->assertDontSee('Tanda Tangan')
            ->assertSee("window.addEventListener('load', function () { window.print(); });", false) // langsung mencetak
            ->assertDontSee(route('presensi.excel', $this->kegiatan)); // tombol Excel cukup di halaman Presensi
    }

    public function test_arsip_hanya_warga_yang_tercatat_dan_kegiatan_kosong(): void
    {
        $arsip = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu September 2026', 'tanggal_kegiatan' => '2026-09-12',
            'status_kegiatan' => false, 'waktu_dibuka' => now(),
        ]);
        $arsip->kehadiran()->create(['nik' => '3404000000000003', 'status_kehadiran' => true, 'waktu_kehadiran' => '2026-09-12 08:00:00']);
        $arsip->kehadiran()->create(['nik' => '3404000000000004', 'status_kehadiran' => false, 'waktu_kehadiran' => '2026-09-12 08:00:00']);

        // Sama dengan halaman Presensi arsip: hanya warga yang tercatat pada kegiatan itu.
        $this->actingAs($this->kader)->get(route('presensi.cetak', $arsip))
            ->assertOk()->assertSeeInOrder(['Budi Balita', 'Hadir', 'Citra Belum Hadir', 'Tidak Hadir'])
            ->assertDontSee('Zaenal Dewasa')->assertSeeInOrder(['Total warga', ': 2 warga']);

        $kosong = Kegiatan::create([
            'nama_kegiatan' => 'Posyandu Agustus 2026', 'tanggal_kegiatan' => '2026-08-12',
            'status_kegiatan' => false, 'waktu_dibuka' => now(),
        ]);
        $this->actingAs($this->kader)->get(route('presensi.cetak', $kosong))
            ->assertOk()->assertSee('Belum ada warga yang tercatat.');
        $this->actingAs($this->kader)->get(route('presensi.excel', $kosong))->assertOk();
    }
}
