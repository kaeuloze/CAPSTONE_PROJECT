<?php

namespace Tests\Feature\Beranda;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Halaman publik / Profil Posyandu (SKF-01; Figma 141:62; proposal revisi Gambar 2.38).
 */
class BerandaTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 09:00:00'));
    }

    private function periksaDewasa(string $nik, string $tanggalKegiatan, string $imt, string $nama = 'Warga Uji'): void
    {
        $kegiatan = Kegiatan::firstOrCreate(['tanggal_kegiatan' => $tanggalKegiatan],
            ['nama_kegiatan' => 'Posyandu '.$tanggalKegiatan, 'status_kegiatan' => false, 'waktu_dibuka' => $tanggalKegiatan.' 08:00:00']);
        $warga = $this->buatWarga($nik, ['nama_warga' => $nama, 'tanggal_lahir' => '1980-01-01']);
        Kehadiran::create(['nik' => $nik, 'id_kegiatan' => $kegiatan->id_kegiatan, 'status_kehadiran' => true, 'waktu_kehadiran' => $tanggalKegiatan.' 08:30:00']);
        $p = $this->buatPemeriksaan($warga, $kegiatan, ['kategori_usia_saat_periksa' => 'dewasa', 'tanggal_pemeriksaan' => $tanggalKegiatan,
            'status_pemeriksaan' => 'selesai', 'jenis_pemeriksaan' => ['rutin']]);
        $p->pengukuranDasar()->create(['status_imt' => $imt, 'status_tekanan_darah' => 'normal']);
    }

    /** Nilai batang "Normal" grafik IMT di halaman. */
    private function imtNormal(string $html): int
    {
        $dokumen = new \DOMDocument();
        $modeErrorLibxml = libxml_use_internal_errors(true);
        try {
            $berhasilDimuat = $dokumen->loadHTML($html);
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($modeErrorLibxml);
        }
        $this->assertTrue($berhasilDimuat, 'HTML halaman beranda tidak dapat dibaca.');

        $xpath = new \DOMXPath($dokumen);

        $judul = $xpath->query('//h3[contains(normalize-space(.), "Distribusi Status Gizi (IMT)")]')->item(0);
        $this->assertNotNull($judul, 'Grafik IMT tidak ditemukan.');

        $kartu = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " group/card ")][1]',
            $judul
        )->item(0);
        $this->assertNotNull($kartu, 'Kartu grafik IMT tidak ditemukan.');

        $label = $xpath->query('.//div[contains(@class, "mt-3 flex gap-3")]/span', $kartu);
        $nilai = $xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " nilai-batang ")]', $kartu);

        foreach ($label as $indeks => $item) {
            if (str_contains($item->textContent, 'Normal')) {
                $this->assertNotNull($nilai->item($indeks), 'Nilai grafik IMT normal tidak ditemukan.');

                return (int) trim($nilai->item($indeks)->textContent);
            }
        }

        $this->fail('Label grafik IMT normal tidak ditemukan.');
    }

    public function test_bisa_dibuka_tanpa_login_dan_berisi_profil(): void
    {
        $this->get(route('beranda'))->assertOk()
            ->assertSee('Selamat Datang di Layanan Kesehatan Masyarakat')
            ->assertSee('Posyandu Mawar 1')
            ->assertSee('Profil')
            ->assertSee('Jadwal')
            ->assertSee('Profil Posyandu')
            ->assertSee('Jadwal Kegiatan')
            ->assertSee('Kontak')
            ->assertSee('Statistik kesehatan agregat')
            ->assertSee('(tanpa identitas warga)')
            ->assertSeeInOrder(['Distribusi Status Gizi (IMT)', 'Grafik Risiko PTM', 'Distribusi Tekanan Darah', 'Skrining TBC'])
            ->assertDontSee('Ekspor ke Excel')
            ->assertSee('href="'.route('login').'"', false);

        $this->actingAs($this->buatKader())->get(route('beranda'))->assertOk(); // tetap bisa dibuka saat login
    }

    public function test_isian_kontak_kosong_tidak_ditampilkan(): void
    {
        $this->get(route('beranda'))->assertDontSee('Narahubung:')->assertDontSee('Pukul');
    }

    public function test_statistik_agregat_bawaan_kegiatan_terakhir_dan_filter(): void
    {
        $this->periksaDewasa('3404000000000001', '2026-09-12', 'normal', 'Siti Rahasia');
        $this->periksaDewasa('3404000000000002', '2026-09-12', 'normal');
        $this->periksaDewasa('3404000000000003', '2026-08-12', 'normal');

        $html = $this->get(route('beranda'))->assertOk()
            ->assertSee('Periode September 2026')                                     // bulan kegiatan terakhir
            ->assertSee('Kegiatan Terakhir')
            ->assertSee('September 2026')
            ->assertDontSee('Siti Rahasia')->assertDontSee('3404000000000001')       // tanpa identitas
            ->getContent();
        $this->assertSame(2, $this->imtNormal($html));

        $this->assertSame(1, $this->imtNormal($this->get(route('beranda', ['bulan' => 8, 'tahun' => 2026]))->getContent()));
        $this->get(route('beranda', ['bulan' => 7, 'tahun' => 2026]))
            ->assertSee('Belum ada kegiatan Posyandu pada')
            ->assertSee('Juli 2026');
        $this->assertSame(0, $this->imtNormal($this->get(route('beranda', ['kategori' => 'lansia']))->getContent()));

        // Isian tidak valid diabaikan (kembali ke bawaan), bukan galat.
        $this->get(route('beranda', ['bulan' => 13, 'tahun' => 2026, 'kategori' => '<x>']))->assertOk()
            ->assertSee('Periode September 2026');
        $this->get(route('beranda', ['bulan' => 12, 'tahun' => 2026]))->assertOk()
            ->assertSee('Periode September 2026'); // bulan yang belum berjalan
    }

    public function test_angka_disimpan_sementara_10_menit(): void
    {
        $this->periksaDewasa('3404000000000001', '2026-10-05', 'normal');
        $this->assertSame(1, $this->imtNormal($this->get(route('beranda'))->getContent()));

        $this->periksaDewasa('3404000000000002', '2026-10-05', 'normal');
        $this->assertSame(1, $this->imtNormal($this->get(route('beranda'))->getContent())); // masih dari cache

        $this->travel(11)->minutes();
        $this->assertSame(2, $this->imtNormal($this->get(route('beranda'))->getContent()));
    }

    public function test_dibatasi_60_permintaan_per_menit(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get(route('beranda'))->assertOk();
        }
        $this->get(route('beranda'))->assertStatus(429);
    }
}
