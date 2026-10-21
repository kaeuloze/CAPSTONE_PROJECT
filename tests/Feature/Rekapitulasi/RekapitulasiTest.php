<?php

namespace Tests\Feature\Rekapitulasi;

use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\Pemeriksaan;
use App\Models\Rekapitulasi;
use App\Models\RekapitulasiDetail;
use App\Models\Warga;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Rekapitulasi + Ekspor Excel (SKF-12; Figma 306:33, 306:103, 316:55, 329:79; Activity Diagram 2.33–2.35).
 */
class RekapitulasiTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private Kader $kader;

    private Kegiatan $awalOkt;

    private Kegiatan $akhirOkt;

    private Kegiatan $sept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 09:00:00'));
        $this->kader = $this->buatKader();

        $this->sept = $this->kegiatanPada('2026-09-12');
        $this->awalOkt = $this->kegiatanPada('2026-10-05');
        $this->akhirOkt = $this->kegiatanPada('2026-10-19');
    }

    private function kegiatanPada(string $tanggal): Kegiatan
    {
        return Kegiatan::create(['nama_kegiatan' => 'Posyandu '.$tanggal, 'tanggal_kegiatan' => $tanggal, 'status_kegiatan' => false, 'waktu_dibuka' => $tanggal.' 08:00:00']);
    }

    private function warga(string $nik, string $lahir, bool $lakiLaki = false, string $kondisi = 'normal'): Warga
    {
        return $this->buatWarga($nik, ['tanggal_lahir' => $lahir, 'jenis_kelamin' => $lakiLaki, 'status_kondisi' => $kondisi]);
    }

    private function hadir(Warga $warga, Kegiatan $kegiatan, bool $hadir = true): void
    {
        Kehadiran::create(['nik' => $warga->nik, 'id_kegiatan' => $kegiatan->id_kegiatan, 'status_kehadiran' => $hadir, 'waktu_kehadiran' => $kegiatan->tanggal_kegiatan->setTime(8, 30)]);
    }

    private function periksa(Warga $warga, Kegiatan $kegiatan, string $kategori, array $detail = [], string $status = 'selesai'): Pemeriksaan
    {
        $p = $this->buatPemeriksaan($warga, $kegiatan, [
            'kategori_usia_saat_periksa' => $kategori,
            'tanggal_pemeriksaan' => $kegiatan->tanggal_kegiatan->toDateString(),
            'status_pemeriksaan' => $status,
            'jenis_pemeriksaan' => ['rutin'],
        ]);
        foreach ($detail as $relasi => $isi) {
            $p->{$relasi}()->create($isi);
        }

        return $p;
    }

    /** Data contoh Oktober 2026 (2 kegiatan) + September 2026. */
    private function isiData(): void
    {
        // Remaja putri 15 th: dua kali diperiksa di Oktober → indikator diambil dari pemeriksaan terbarunya.
        $r1 = $this->warga('3404000000000001', '2011-03-01');
        $this->hadir($r1, $this->awalOkt);
        $this->hadir($r1, $this->akhirOkt);
        $this->periksa($r1, $this->awalOkt, 'remaja', [
            'pengukuranDasar' => ['status_imt' => 'normal', 'status_lingkar_perut' => 'normal', 'status_tekanan_darah' => 'normal', 'kadar_hb' => 11.0],
            'skriningSensorik' => ['status_penglihatan' => 'normal', 'status_pendengaran' => 'gangguan'],
            'skriningTbc' => ['status_gejala_tbc' => 'normal'],
        ]);
        $this->periksa($r1, $this->akhirOkt, 'remaja', [
            'pengukuranDasar' => ['status_imt' => 'gemuk', 'status_lingkar_perut' => 'normal', 'status_tekanan_darah' => 'tinggi'],
            'skriningTbc' => ['status_gejala_tbc' => 'berisiko'],
            'penyuluhanRujukan' => ['topik_penyuluhan' => 'Gizi seimbang', 'status_rujukan' => 'rujuk_puskesmas', 'tanggal_rujukan' => '2026-10-19'],
        ]);
        // Remaja putra 10 th: hadir, pemeriksaan masih draft → hanya dihitung "Datang".
        $r2 = $this->warga('3404000000000002', '2016-01-10', true);
        $this->hadir($r2, $this->akhirOkt);
        $this->periksa($r2, $this->akhirOkt, 'remaja', ['pengukuranDasar' => ['status_imt' => 'normal']], 'draft');
        // Remaja 12 th tidak hadir → hanya sasaran.
        $r3 = $this->warga('3404000000000003', '2014-05-05', true);
        $this->hadir($r3, $this->akhirOkt, false);

        // Ibu hamil (25 th) & ibu menyusui: tidak masuk sasaran Dewasa.
        $hamil = $this->warga('3404000000000004', '2001-02-02', false, 'hamil');
        $this->hadir($hamil, $this->akhirOkt);
        $this->periksa($hamil, $this->akhirOkt, 'ibu_hamil', [
            'pemeriksaanBumil' => ['jenis_ibu' => 'hamil', 'status_ploting_imt' => 'normal', 'status_ploting_tekanan_darah' => 'normal',
                'status_ploting_lila' => 'kek', 'jumlah_ttd_mms' => 30, 'status_konsumsi_ttd_rutin' => true, 'komposisi_mt_bumil_kek' => 'Biskuit 2 keping',
                'status_rutin_mt_kek' => false, 'ikut_kelas_ibu_hamil' => false],
            'skriningTbc' => ['status_gejala_tbc' => 'berisiko'],
        ]);
        $menyusui = $this->warga('3404000000000005', '1998-07-07', false, 'menyusui');
        $this->hadir($menyusui, $this->awalOkt);
        $this->periksa($menyusui, $this->awalOkt, 'ibu_nifas', [
            'pemeriksaanBumil' => ['jenis_ibu' => 'nifas', 'status_ploting_imt' => 'gemuk', 'status_ploting_tekanan_darah' => 'tinggi'],
        ]);

        // Dewasa 45 th & lansia 70 th.
        $dewasa = $this->warga('3404000000000006', '1981-04-04', true);
        $this->hadir($dewasa, $this->awalOkt);
        $this->periksa($dewasa, $this->awalOkt, 'dewasa', [
            'pengukuranDasar' => ['status_imt' => 'normal', 'status_lingkar_perut' => 'berisiko', 'status_tekanan_darah' => 'normal', 'kadar_gula_darah' => 210],
            'skriningPuma' => ['skor_puma_total' => 7, 'status_ppok' => 'berisiko'],
        ]);
        $lansia = $this->warga('3404000000000007', '1956-06-06');
        $this->hadir($lansia, $this->akhirOkt);
        $this->periksa($lansia, $this->akhirOkt, 'lansia', [
            'pengukuranDasar' => ['status_imt' => 'kurus', 'status_lingkar_perut' => 'normal', 'status_tekanan_darah' => 'tinggi', 'kadar_gula_darah' => 150],
            'skriningPuma' => ['skor_puma_total' => 3, 'status_ppok' => 'tidak_berisiko'],
            'skriningSensorik' => ['status_penglihatan' => 'gangguan', 'status_pendengaran' => 'normal'],
        ]);
        // Dewasa lain diperiksa September → tidak ikut rekap Oktober.
        $sept = $this->warga('3404000000000008', '1990-08-08');
        $this->hadir($sept, $this->sept);
        $this->periksa($sept, $this->sept, 'dewasa', ['pengukuranDasar' => ['status_imt' => 'obesitas', 'kadar_gula_darah' => 250]]);

        // Balita: tidak masuk rekap mana pun.
        $this->warga('3404000000000009', '2024-01-01');
    }

    /** @return array<string, int> kode_item => nilai dari tabel di layar */
    private function nilaiDiLayar(string $kategori, array $query = []): array
    {
        $html = $this->actingAs($this->kader)->get(route('rekapitulasi.show', array_merge(['kategori' => $kategori], $query)))->assertOk()->getContent();
        preg_match_all('#<tr data-kode="([A-Z_]+)">\s*<td>[^<]*</td>\s*<td[^>]*>(\d+)</td>#u', $html, $m);

        return array_map('intval', array_combine($m[1], $m[2]));
    }

    public function test_wajib_login_dan_menu_aktif(): void
    {
        $this->get(route('rekapitulasi.index'))->assertRedirect(route('login'));
        $this->get(route('rekapitulasi.show', 'remaja'))->assertRedirect(route('login'));
        $this->get(route('rekapitulasi.ekspor', 'remaja'))->assertRedirect(route('login'));

        $html = $this->actingAs($this->kader)->get(route('rekapitulasi.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"\s*><i aria-hidden="true"><\/i>Rekapitulasi/', preg_replace('/\s+/', ' ', $html));
    }

    public function test_halaman_pilihan_kategori(): void
    {
        $this->actingAs($this->kader)->get(route('rekapitulasi.index'))->assertOk()
            ->assertSee('Pilih Kategori Rekapitulasi')
            ->assertSeeInOrder(['Anak Usia Sekolah &amp; Remaja', 'Ibu Hamil / Nifas', 'Dewasa dan Lansia'], false)
            ->assertSee('href="'.route('rekapitulasi.show', 'ibu_hamil_nifas').'"', false)
            ->assertSee('Oktober 2026'); // periode kegiatan terakhir

        $this->actingAs($this->kader)->get('/rekapitulasi/balita')->assertNotFound();
    }

    public function test_rekap_remaja(): void
    {
        $this->isiData();

        $this->actingAs($this->kader)->get(route('rekapitulasi.show', 'remaja'))->assertOk()
            ->assertSee('Rekapitulasi — Anak Usia Sekolah &amp; Remaja', false)
            ->assertSee('Oktober 2026')->assertSee('Ekspor ke Excel (.xlsx)')
            ->assertSeeInOrder(['Jumlah Remaja 5–17 Tahun', 'Jumlah Remaja 5–17 Tahun Datang', 'IMT Normal (18,5 – 24,9)', 'IMT Risiko (&lt; 18,5 / ≥ 25)',
                'LP Remaja Normal', 'TD Remaja Normal (&lt; 130/85 mmHg)', 'Bergejala TBC (memenuhi 2 gejala)', 'Kadar HB Normal Remaja Putri (≥ 12 g/dL)',
                'Tes Penglihatan Normal', 'Tes Pendengaran Risiko', 'Sasaran Mendapat Edukasi / Penyuluhan', 'Jumlah Remaja 5–17 Tahun Dirujuk'], false);

        $this->assertSame([
            'SASARAN_REMAJA' => 3, 'DATANG_REMAJA' => 2,
            'IMT_NORMAL' => 0, 'IMT_RISIKO' => 1,           // pemeriksaan terbaru: gemuk
            'LP_NORMAL' => 1, 'LP_RISIKO' => 0,
            'TD_NORMAL' => 0, 'TD_RISIKO' => 1,
            'TBC_BERGEJALA' => 1,
            'HB_NORMAL' => 0, 'HB_RISIKO' => 1,             // HB dari pemeriksaan awal bulan (terakhir yang memuat HB)
            'PENGLIHATAN_NORMAL' => 1, 'PENGLIHATAN_RISIKO' => 0, 'PENDENGARAN_NORMAL' => 0, 'PENDENGARAN_RISIKO' => 1,
            'EDUKASI' => 1, 'DIRUJUK_REMAJA' => 1,
        ], $this->nilaiDiLayar('remaja'));
    }

    public function test_rekap_dewasa_lansia(): void
    {
        $this->isiData();

        $nilai = $this->nilaiDiLayar('dewasa_lansia', ['bulan' => 10, 'tahun' => 2026]);
        $this->assertSame([
            'SASARAN_DEWASA' => 2, 'SASARAN_LANSIA' => 1, 'DATANG_DEWASA' => 1, 'DATANG_LANSIA' => 1,
            'IMT_NORMAL' => 1, 'IMT_RISIKO' => 1, 'LP_NORMAL' => 1, 'LP_RISIKO' => 1, 'TD_NORMAL' => 1, 'TD_RISIKO' => 1,
            'GDS_NORMAL' => 1, 'GDS_BERISIKO' => 1, 'TBC_BERGEJALA' => 0,
            'PENGLIHATAN_NORMAL' => 0, 'PENGLIHATAN_RISIKO' => 1, 'PENDENGARAN_NORMAL' => 1, 'PENDENGARAN_RISIKO' => 0,
            'PUMA_NORMAL' => 1, 'PUMA_BERISIKO' => 1,
        ], $nilai);

        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['dewasa_lansia', 'bulan' => 10, 'tahun' => 2026]))
            ->assertSee('Jumlah Sasaran Lansia (≥ 60 Tahun)')->assertSee('GDS Berisiko (≥ 200 mg/dl)')
            ->assertSee('Skrining PPOK PUMA Berisiko (≥ 6)')->assertDontSee('Kadar HB');

        // September: hanya warga yang diperiksa di September.
        $sept = $this->nilaiDiLayar('dewasa_lansia', ['bulan' => 9, 'tahun' => 2026]);
        $this->assertSame([1, 1, 1], [$sept['DATANG_DEWASA'], $sept['IMT_RISIKO'], $sept['GDS_BERISIKO']]);
        $this->assertSame(0, $sept['DATANG_LANSIA']);
    }

    public function test_rekap_ibu_hamil_nifas(): void
    {
        $this->isiData();

        $this->assertSame([
            'SASARAN_HAMIL' => 1, 'SASARAN_NIFAS' => 1, 'DATANG_HAMIL' => 1, 'DATANG_NIFAS' => 1,
            'IMT_KIA' => 1, 'TD_NORMAL' => 1, 'TD_RISIKO' => 1, 'LILA_NORMAL' => 0, 'LILA_KEK' => 1, 'TBC_BERGEJALA' => 1,
            'TTD_MENDAPAT' => 1, 'TTD_RUTIN' => 1, 'TTD_TIDAK_RUTIN' => 0,
            'PMT_MENDAPAT' => 1, 'PMT_RUTIN' => 0, 'PMT_TIDAK_RUTIN' => 1,
            'KELAS_IKUT' => 0, 'KELAS_TIDAK' => 1,
        ], $this->nilaiDiLayar('ibu_hamil_nifas'));
    }

    public function test_hasil_disimpan_ke_tabel_rekapitulasi_tanpa_ganda(): void
    {
        $this->isiData();

        $this->nilaiDiLayar('remaja');
        $this->nilaiDiLayar('remaja');

        $rekap = Rekapitulasi::where(['kategori_rekap' => 'remaja', 'periode_bulan' => 10, 'periode_tahun' => 2026])->sole();
        $this->assertSame('2026-10-21', $rekap->tanggal_dibuat->toDateString());
        $this->assertStringContainsString('Posyandu 2026-10-19', $rekap->catatan);
        $this->assertSame(17, $rekap->detail()->count());
        $this->assertSame(['SASARAN_REMAJA', 3, 1], [$rekap->detail->first()->kode_item, $rekap->detail->first()->nilai, $rekap->detail->first()->urutan]);

        // Data berubah → angka tersimpan ikut diperbarui saat dibuka lagi.
        $this->warga('3404000000000010', '2012-12-12');
        $this->nilaiDiLayar('remaja');
        $this->assertSame(4, RekapitulasiDetail::where('id_rekapitulasi', $rekap->id_rekapitulasi)->where('kode_item', 'SASARAN_REMAJA')->value('nilai'));
        $this->assertSame(1, Rekapitulasi::count());
    }

    public function test_periode_tanpa_kegiatan_dan_validasi_filter(): void
    {
        $nilai = $this->nilaiDiLayar('remaja', ['bulan' => 8, 'tahun' => 2026]);
        $this->assertSame(0, array_sum($nilai));
        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['remaja', 'bulan' => 8, 'tahun' => 2026]))
            ->assertSee('Tidak ada kegiatan posyandu pada periode ini');

        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['remaja', 'bulan' => 13, 'tahun' => 2026]))->assertSessionHasErrors('bulan');
        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['remaja', 'bulan' => 'x', 'tahun' => 2026]))->assertSessionHasErrors('bulan');
        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['remaja', 'bulan' => 11, 'tahun' => 2026]))
            ->assertSessionHasErrors(['bulan' => 'Periode belum berjalan. Pilih bulan ini atau bulan sebelumnya.']);
        $this->actingAs($this->kader)->get(route('rekapitulasi.show', ['remaja', 'bulan' => 5]))->assertSessionHasErrors('tahun');
    }

    public function test_ekspor_excel_satu_bulan(): void
    {
        $this->isiData();

        $respon = $this->actingAs($this->kader)->get(route('rekapitulasi.ekspor', ['remaja', 'bulan' => 10, 'tahun' => 2026]));
        $respon->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('rekap-remaja-2026-10.xlsx', $respon->headers->get('Content-Disposition'));

        $berkas = tempnam(sys_get_temp_dir(), 'rekap').'.xlsx';
        file_put_contents($berkas, $respon->streamedContent());
        $lembar = IOFactory::load($berkas)->getActiveSheet();
        @unlink($berkas);

        $this->assertSame('REKAPITULASI HASIL PEMERIKSAAN ANAK USIA SEKOLAH DAN REMAJA', $lembar->getCell('A1')->getValue());
        $this->assertSame('POSYANDU MAWAR 1', $lembar->getCell('A2')->getValue());
        $this->assertSame(': Banyuraden', $lembar->getCell('B5')->getValue());
        $this->assertSame(': Oktober 2026', $lembar->getCell('B7')->getValue());
        $this->assertSame('Bulan dan Tahun: Oktober 2026', $lembar->getCell('C9')->getValue());
        $this->assertSame(['Jumlah Remaja 5–17 Tahun', 3], [$lembar->getCell('B10')->getValue(), $lembar->getCell('C10')->getValue()]);
        $this->assertSame(['Jumlah Remaja 5–17 Tahun Dirujuk', 1], [$lembar->getCell('B26')->getValue(), $lembar->getCell('C26')->getValue()]);
        $this->assertSame(1, Rekapitulasi::count()); // ekspor juga menyimpan rekap
    }
}
