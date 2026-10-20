<?php

namespace Tests\Feature\Dashboard;

use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\Pemeriksaan;
use App\Models\Penyakit;
use App\Models\RiwayatPtm;
use App\Models\Warga;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Dashboard (SKF-03; Figma 409:55 & 409:185; proposal revisi Gambar 2.40–2.41).
 */
class DashboardTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 09:00:00'));
        $this->kader = $this->buatKader();
    }

    private function kegiatanPada(string $tanggal): Kegiatan
    {
        return Kegiatan::create(['nama_kegiatan' => 'Posyandu '.$tanggal, 'tanggal_kegiatan' => $tanggal, 'status_kegiatan' => false, 'waktu_dibuka' => $tanggal.' 08:00:00']);
    }

    private function periksa(string $nik, string $lahir, Kegiatan $kegiatan, string $kategori, array $detail, array $ptm = [], string $kondisi = 'normal', string $status = 'selesai'): Warga
    {
        $warga = Warga::find($nik) ?? $this->buatWarga($nik, ['tanggal_lahir' => $lahir, 'status_kondisi' => $kondisi]);
        foreach ($ptm as $idPenyakit) {
            RiwayatPtm::firstOrCreate(['nik' => $nik, 'id_penyakit' => $idPenyakit], ['sumber_riwayat' => 'kartu_bantu', 'tanggal_dicatat' => '2026-01-01']);
        }
        Kehadiran::create(['nik' => $nik, 'id_kegiatan' => $kegiatan->id_kegiatan, 'status_kehadiran' => true, 'waktu_kehadiran' => $kegiatan->tanggal_kegiatan->setTime(8, 0)]);
        $p = $this->buatPemeriksaan($warga, $kegiatan, ['kategori_usia_saat_periksa' => $kategori, 'tanggal_pemeriksaan' => $kegiatan->tanggal_kegiatan->toDateString(),
            'status_pemeriksaan' => $status, 'jenis_pemeriksaan' => ['rutin']]);
        foreach ($detail as $relasi => $isi) {
            $p->{$relasi}()->create($isi);
        }

        return $warga;
    }

    /** @return array{0: Penyakit, 1: Penyakit} */
    private function isiData(): array
    {
        $hipertensi = Penyakit::create(['nama_penyakit' => 'Hipertensi']);
        $dm = Penyakit::create(['nama_penyakit' => 'DM']);
        $awal = $this->kegiatanPada('2026-10-05');
        $akhir = $this->kegiatanPada('2026-10-19');
        $sept = $this->kegiatanPada('2026-09-12');

        // Remaja: dua kali periksa bulan ini → yang dihitung pemeriksaan terbaru (kurus).
        $this->periksa('3404000000000001', '2011-03-01', $awal, 'remaja', ['pengukuranDasar' => ['status_imt' => 'normal'], 'skriningTbc' => ['status_gejala_tbc' => 'normal']]);
        $this->periksa('3404000000000001', '2011-03-01', $akhir, 'remaja', ['pengukuranDasar' => ['status_imt' => 'kurus', 'status_tekanan_darah' => 'tinggi']]);
        // Dewasa: obesitas (→ Risiko Gemuk), TD tinggi, TBC bergejala, riwayat Hipertensi + DM.
        $this->periksa('3404000000000002', '1980-01-01', $awal, 'dewasa',
            ['pengukuranDasar' => ['status_imt' => 'obesitas', 'status_tekanan_darah' => 'tinggi'], 'skriningTbc' => ['status_gejala_tbc' => 'berisiko']], [$hipertensi->id_penyakit, $dm->id_penyakit]);
        // Lansia: normal, riwayat Hipertensi.
        $this->periksa('3404000000000003', '1950-01-01', $akhir, 'lansia',
            ['pengukuranDasar' => ['status_imt' => 'normal', 'status_tekanan_darah' => 'normal'], 'skriningTbc' => ['status_gejala_tbc' => 'normal']], [$hipertensi->id_penyakit]);
        // Ibu hamil: ploting IMT KIA gemuk (pengukuran dasar tanpa status IMT).
        $this->periksa('3404000000000004', '2000-01-01', $akhir, 'ibu_hamil',
            ['pemeriksaanBumil' => ['jenis_ibu' => 'hamil', 'status_ploting_imt' => 'gemuk'], 'skriningTbc' => ['status_gejala_tbc' => 'normal']], [], 'hamil');
        // Draft & riwayat PTM warga yang tidak diperiksa → tidak dihitung.
        $this->periksa('3404000000000005', '1985-01-01', $akhir, 'dewasa', ['pengukuranDasar' => ['status_imt' => 'normal']], [$dm->id_penyakit], 'normal', 'draft');
        // September.
        $this->periksa('3404000000000006', '1975-01-01', $sept, 'dewasa', ['pengukuranDasar' => ['status_imt' => 'normal', 'status_tekanan_darah' => 'normal']], [$dm->id_penyakit]);

        return [$hipertensi, $dm];
    }

    private function angka(array $query = []): array
    {
        return $this->actingAs($this->kader)->getJson(route('dashboard.data', $query))->assertOk()->json('grafik');
    }

    public function test_wajib_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->getJson(route('dashboard.data'))->assertUnauthorized();
        $this->get(route('dashboard.ekspor'))->assertRedirect(route('login'));
    }

    public function test_tab_bulan_ini_menampilkan_empat_grafik(): void
    {
        $this->isiData();

        $this->actingAs($this->kader)->get(route('dashboard'))->assertOk()
            ->assertSee('Kader Uji')
            ->assertSee('Bulan Ini (Oktober 2026)')->assertSee('Arsip Bulan Sebelumnya')->assertSee('Ekspor ke Excel (.xlsx)')
            ->assertSeeInOrder(['Distribusi Status Gizi (IMT)', 'Grafik Risiko PTM', 'Distribusi Tekanan Darah', 'Skrining TBC'])
            ->assertSeeInOrder(['Normal (18,5–24,9)', 'Risiko Kurus (&lt;18,5)', 'Risiko Gemuk (≥25)'], false)
            ->assertSee('Filter Kategori: Semua')
            ->assertSee('data-url="'.e(route('dashboard.data')).'"', false)
            ->assertSee('js/dashboard.js');
    }

    public function test_angka_grafik_bulan_ini(): void
    {
        [$hipertensi, $dm] = $this->isiData();

        $this->assertSame([
            'imt' => ['normal' => 1, 'kurus' => 1, 'gemuk' => 2],     // lansia normal; remaja kurus (terbaru); dewasa obesitas + bumil gemuk
            'ptm' => [(string) $hipertensi->id_penyakit => 2, (string) $dm->id_penyakit => 1],
            'td' => ['normal' => 1, 'risiko' => 1],                   // hanya dewasa & lansia (TD remaja tidak dihitung)
            'tbc' => ['tidak' => 3, 'bergejala' => 1],
        ], $this->angka());
    }

    public function test_filter_kategori_imt(): void
    {
        $this->isiData();

        $this->assertSame(['normal' => 0, 'kurus' => 0, 'gemuk' => 1], $this->angka(['kategori' => 'ibu'])['imt']);
        $this->assertSame(['normal' => 0, 'kurus' => 1, 'gemuk' => 0], $this->angka(['kategori' => 'remaja'])['imt']);
        $this->assertSame(['normal' => 1, 'kurus' => 0, 'gemuk' => 0], $this->angka(['kategori' => 'lansia'])['imt']);
        // Filter hanya untuk grafik IMT.
        $this->assertSame(['tidak' => 3, 'bergejala' => 1], $this->angka(['kategori' => 'lansia'])['tbc']);
        $this->actingAs($this->kader)->getJson(route('dashboard.data', ['kategori' => 'balita']))->assertUnprocessable();
    }

    public function test_tab_arsip_bulan_sebelumnya(): void
    {
        [, $dm] = $this->isiData();

        // Bawaan: bulan lalu (September 2026).
        $this->actingAs($this->kader)->get(route('dashboard', ['tab' => 'arsip']))->assertOk()
            ->assertSee('Menampilkan data: <strong>September 2026</strong>', false)
            ->assertSee('Pilih periode:')->assertDontSee('id="indikator-langsung"', false)
            ->assertDontSee('data-url=', false);

        $html = $this->actingAs($this->kader)->get(route('dashboard', ['tab' => 'arsip', 'bulan' => 9, 'tahun' => 2026]))->getContent();
        $this->assertMatchesRegularExpression('#data-grafik="ptm".*?data-kunci="'.$dm->id_penyakit.'">\s*<span class="nilai-batang">1</span>#s', $html);

        $this->actingAs($this->kader)->get(route('dashboard', ['tab' => 'arsip', 'bulan' => 10, 'tahun' => 2026]))
            ->assertSessionHasErrors(['bulan' => 'Arsip hanya untuk bulan sebelumnya. Bulan ini ada di tab Bulan Ini.']);
        $this->actingAs($this->kader)->get(route('dashboard', ['tab' => 'arsip', 'bulan' => 8, 'tahun' => 2026]))
            ->assertSee('Tidak ada kegiatan posyandu pada Agustus 2026');
    }

    public function test_tanpa_kegiatan_bulan_ini(): void
    {
        $this->actingAs($this->kader)->get(route('dashboard'))->assertOk()
            ->assertSee('belum ada kegiatan posyandu bulan ini')
            ->assertSee('Tidak ada kegiatan posyandu pada Oktober 2026');
        $this->assertSame(['normal' => 0, 'kurus' => 0, 'gemuk' => 0], $this->angka()['imt']);
    }

    public function test_ekspor_excel(): void
    {
        [$hipertensi] = $this->isiData();

        $respon = $this->actingAs($this->kader)->get(route('dashboard.ekspor', ['kategori' => 'remaja']));
        $respon->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('dashboard-2026-10.xlsx', $respon->headers->get('Content-Disposition'));

        $berkas = tempnam(sys_get_temp_dir(), 'dash').'.xlsx';
        file_put_contents($berkas, $respon->streamedContent());
        $lembar = IOFactory::load($berkas)->getActiveSheet();
        @unlink($berkas);

        $this->assertSame('DASHBOARD POSYANDU MAWAR 1', $lembar->getCell('A1')->getValue());
        $this->assertSame('Periode: Oktober 2026', $lembar->getCell('A2')->getValue());
        $this->assertSame('Distribusi Status Gizi (IMT) — Remaja', $lembar->getCell('A6')->getValue());
        $this->assertSame(['Risiko Kurus (<18,5)', 1], [$lembar->getCell('A8')->getValue(), $lembar->getCell('B8')->getValue()]);
        $this->assertSame(['Grafik Risiko PTM', 'Hipertensi', 2], [$lembar->getCell('A11')->getValue(), $lembar->getCell('A12')->getValue(), $lembar->getCell('B12')->getValue()]);

        $this->actingAs($this->kader)->get(route('dashboard.ekspor', ['tab' => 'arsip', 'bulan' => 9, 'tahun' => 2026]))
            ->assertOk()->assertDownload('dashboard-2026-09.xlsx');
    }
}
