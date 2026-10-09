<?php

namespace Tests\Feature\Models;

use App\Enums\JenisPemeriksaan;
use App\Enums\StatusAsi;
use App\Enums\StatusKondisi;
use App\Enums\StatusRujukan;
use App\Enums\SumberRiwayat;
use App\Models\Kader;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Penyakit;
use App\Models\Rekapitulasi;
use App\Models\Vaksin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Memastikan model, relasi, Enum, dan pengamanan Kader bekerja sesuai ERD.
 */
class RelasiModelTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_setiap_model_memakai_tabel_dan_primary_key_sesuai_erd(): void
    {
        $harapan = [
            'Kader' => ['kader', 'id_kader'], 'Warga' => ['warga', 'nik'], 'Kegiatan' => ['kegiatan', 'id_kegiatan'],
            'Kehadiran' => ['kehadiran', 'id_kehadiran'], 'Pemeriksaan' => ['pemeriksaan', 'id_pemeriksaan'],
            'PengukuranDasar' => ['pengukuran_dasar', 'id_pengukuran'], 'SkriningTbc' => ['skrining_tbc', 'id_skrining_tbc'],
            'SkriningSensorik' => ['skrining_sensorik', 'id_skrining_sensorik'], 'SkriningPuma' => ['skrining_puma', 'id_puma'],
            'SkriningAks' => ['skrining_aks', 'id_aks'], 'SkriningSkilas' => ['skrining_skilas', 'id_skilas'],
            'PemeriksaanBumil' => ['pemeriksaan_bumil', 'id_bumil'], 'PenyuluhanRujukan' => ['penyuluhan_rujukan', 'id_penyuluhan'],
            'Vaksin' => ['vaksin', 'id_vaksin'], 'Vaksinasi' => ['vaksinasi', 'id_vaksinasi'], 'AsiEksklusif' => ['asi_eksklusif', 'id_asi'],
            'Penyakit' => ['penyakit', 'id_penyakit'], 'RiwayatPtm' => ['riwayat_ptm', 'id_riwayat'],
            'Rekapitulasi' => ['rekapitulasi', 'id_rekapitulasi'], 'RekapitulasiDetail' => ['rekapitulasi_detail', 'id_rekap_detail'],
        ];

        foreach ($harapan as $nama => [$tabel, $pk]) {
            $model = new ("App\\Models\\$nama");
            $this->assertSame($tabel, $model->getTable(), $nama);
            $this->assertSame($pk, $model->getKeyName(), $nama);
            $model->newQuery()->limit(1)->get(); // tabel benar-benar bisa dibaca
        }
    }

    public function test_warga_memakai_nik_dan_enum(): void
    {
        $this->buatWarga('3404000000000077', ['status_kondisi' => 'hamil']);
        $warga = \App\Models\Warga::find('3404000000000077');

        $this->assertSame(StatusKondisi::Hamil, $warga->status_kondisi);
        $this->assertSame('Ibu Hamil', $warga->status_kondisi->label());
        $this->assertSame('Perempuan', $warga->jenis_kelamin_label);
    }

    public function test_kolom_set_jenis_pemeriksaan_dua_arah(): void
    {
        $p = $this->buatPemeriksaan($this->buatWarga(), $this->buatKegiatan(), [
            'jenis_pemeriksaan' => [JenisPemeriksaan::Rutin, 'tahunan'],
        ]);

        $this->assertSame('rutin,tahunan', DB::table('pemeriksaan')->where('id_pemeriksaan', $p->id_pemeriksaan)->value('jenis_pemeriksaan'));
        $this->assertEquals([JenisPemeriksaan::Rutin, JenisPemeriksaan::Tahunan], $p->refresh()->jenis_pemeriksaan);
    }

    public function test_pemeriksaan_terhubung_ke_semua_tabel_detail(): void
    {
        $warga = $this->buatWarga();
        $kegiatan = $this->buatKegiatan();
        $p = $this->buatPemeriksaan($warga, $kegiatan);

        $p->pengukuranDasar()->create(['berat_badan' => 58.5, 'tekanan_darah' => '120/80']);
        $p->skriningTbc()->create(['batuk_terus_menerus' => true]);
        $p->skriningSensorik()->create(['mata_kanan' => 'normal']);
        $p->skriningPuma()->create(['gejala_dahak' => true]);
        $p->skriningAks()->create(['skor_bab' => 2]);
        $p->skriningSkilas()->create(['tes_melihat' => true]);
        $p->pemeriksaanBumil()->create(['jenis_ibu' => 'hamil']);
        $p->penyuluhanRujukan()->create(['topik_penyuluhan' => 'Gizi seimbang']);

        $p = Pemeriksaan::with(['warga', 'kegiatan', 'pengukuranDasar', 'skriningTbc', 'skriningSensorik', 'skriningPuma',
            'skriningAks', 'skriningSkilas', 'pemeriksaanBumil', 'penyuluhanRujukan'])->find($p->id_pemeriksaan);

        $this->assertSame($warga->nik, $p->warga->nik);
        $this->assertSame($kegiatan->id_kegiatan, $p->kegiatan->id_kegiatan);
        $this->assertSame('58.50', $p->pengukuranDasar->berat_badan);
        $this->assertTrue($p->skriningTbc->batuk_terus_menerus);
        $this->assertTrue($p->skriningPuma->gejala_dahak);
        $this->assertSame(StatusRujukan::TidakDirujuk, $p->penyuluhanRujukan->refresh()->status_rujukan);
        $this->assertSame($p->id_pemeriksaan, $p->skriningAks->pemeriksaan->id_pemeriksaan);
        $this->assertTrue(Pemeriksaan::draft()->whereKey($p->getKey())->exists());
    }

    public function test_kegiatan_kehadiran_dan_scope(): void
    {
        $warga = $this->buatWarga();
        $kegiatan = $this->buatKegiatan();
        $kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true, 'waktu_kehadiran' => now()]);

        $this->assertTrue(Kegiatan::berlangsung()->whereKey($kegiatan->id_kegiatan)->exists());
        $this->assertSame($warga->nik, $kegiatan->kehadiran()->first()->warga->nik);
    }

    public function test_warga_penyakit_vaksinasi_dan_asi(): void
    {
        $warga = $this->buatWarga();
        $penyakit = Penyakit::create(['nama_penyakit' => 'Hipertensi']);
        $warga->penyakit()->attach($penyakit->id_penyakit, ['tanggal_dicatat' => now()->toDateString()]);

        $this->assertSame('Hipertensi', $warga->penyakit()->first()->nama_penyakit);
        $this->assertSame(SumberRiwayat::KartuBantu, $warga->riwayatPtm()->first()->sumber_riwayat);

        $vaksin = Vaksin::create(['nama_vaksin' => 'Polio 1']);
        $warga->vaksinasi()->create(['id_vaksin' => $vaksin->id_vaksin, 'status_vaksinasi' => true]);
        $warga->asiEksklusif()->create(['bulan_ke' => 1, 'status_asi' => 'ya', 'tanggal_dicatat' => now()]);

        $this->assertSame('Polio 1', $warga->vaksinasi()->first()->vaksin->nama_vaksin);
        $this->assertNull($warga->vaksinasi()->first()->kegiatan);
        $this->assertSame(StatusAsi::Ya, $warga->asiEksklusif()->first()->status_asi);
    }

    public function test_detail_rekapitulasi_terurut(): void
    {
        $rekap = Rekapitulasi::create(['kategori_rekap' => 'remaja', 'periode_bulan' => 10, 'periode_tahun' => 2026, 'tanggal_dibuat' => now()]);
        $rekap->detail()->create(['kode_item' => 'B', 'nama_item' => 'Item 2', 'nilai' => 5, 'urutan' => 2]);
        $rekap->detail()->create(['kode_item' => 'A', 'nama_item' => 'Item 1', 'nilai' => 3, 'urutan' => 1]);

        $this->assertSame(['A', 'B'], $rekap->detail()->pluck('kode_item')->all());
    }

    public function test_password_kader_aman(): void
    {
        $kader = $this->buatKader('kader_aman', 'RahasiaUji123');

        $this->assertSame('password_hash', $kader->getAuthPasswordName());
        $this->assertNotSame('RahasiaUji123', DB::table('kader')->where('username', 'kader_aman')->value('password_hash'));
        $this->assertArrayNotHasKey('password_hash', $kader->toArray());
        $this->assertNotContains('id_kader', (new Kader)->getFillable());
    }
}
