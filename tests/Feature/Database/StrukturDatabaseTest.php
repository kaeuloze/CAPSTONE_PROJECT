<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Memastikan struktur database sesuai ERD Gambar 2.2 & keputusan teknis.
 */
class StrukturDatabaseTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private const TABEL_ERD = [
        'kader', 'penyakit', 'vaksin', 'warga', 'riwayat_ptm', 'asi_eksklusif', 'kegiatan', 'kehadiran',
        'vaksinasi', 'pemeriksaan', 'pengukuran_dasar', 'skrining_tbc', 'skrining_sensorik', 'skrining_puma',
        'skrining_aks', 'skrining_skilas', 'pemeriksaan_bumil', 'penyuluhan_rujukan', 'rekapitulasi', 'rekapitulasi_detail',
    ];

    public function test_semua_20_tabel_erd_tersedia(): void
    {
        foreach (self::TABEL_ERD as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "Tabel $tabel tidak ada");
        }
        $this->assertFalse(Schema::hasTable('users'), 'Tabel users bawaan seharusnya sudah dihapus');
    }

    public function test_aturan_hapus_relasi_sesuai_keputusan(): void
    {
        $aturan = collect(DB::select(
            'SELECT TABLE_NAME t, REFERENCED_TABLE_NAME r, DELETE_RULE d FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ?',
            [DB::getDatabaseName()]
        ));

        $this->assertCount(19, $aturan);
        foreach ($aturan as $a) {
            $harapan = in_array($a->r, ['pemeriksaan', 'rekapitulasi']) ? 'CASCADE' : 'RESTRICT';
            $this->assertSame($harapan, $a->d, "{$a->t} -> {$a->r}");
        }
    }

    public function test_tipe_kolom_hasil_keputusan(): void
    {
        $tipe = fn (string $tabel, string $kolom) => DB::selectOne(
            'SELECT COLUMN_TYPE c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $tabel, $kolom]
        )?->c;

        $this->assertSame('varchar(16)', $tipe('pemeriksaan', 'nik'));
        $this->assertSame("set('rutin','enam_bulanan','tahunan')", $tipe('pemeriksaan', 'jenis_pemeriksaan'));
        $this->assertSame('datetime', $tipe('kehadiran', 'waktu_kehadiran'));
        $this->assertNull($tipe('vaksinasi', 'jenis_vaksinasi'));
        $this->assertNotNull($tipe('skrining_puma', 'gejala_dahak'));
    }

    public function test_draft_dengan_isian_kosong_boleh_disimpan(): void
    {
        $p = $this->buatPemeriksaan($this->buatWarga(), $this->buatKegiatan());
        $p->pengukuranDasar()->create(['berat_badan' => 60]); // isian lain kosong

        $this->assertDatabaseHas('pengukuran_dasar', ['id_pemeriksaan' => $p->id_pemeriksaan, 'tinggi_badan' => null]);
        $this->assertSame('draft', $p->refresh()->status_pemeriksaan->value);
    }

    public function test_warga_yang_punya_riwayat_tidak_bisa_dihapus(): void
    {
        $warga = $this->buatWarga();
        $this->buatPemeriksaan($warga, $this->buatKegiatan());

        $this->expectException(QueryException::class);
        $warga->delete();
    }

    public function test_warga_tanpa_riwayat_bisa_dihapus(): void
    {
        $warga = $this->buatWarga();
        $warga->delete();

        $this->assertDatabaseMissing('warga', ['nik' => $warga->nik]);
    }

    public function test_pemeriksaan_ganda_pada_kunjungan_yang_sama_ditolak(): void
    {
        $warga = $this->buatWarga();
        $kegiatan = $this->buatKegiatan();
        $this->buatPemeriksaan($warga, $kegiatan);

        $this->expectException(QueryException::class);
        $this->buatPemeriksaan($warga, $kegiatan);
    }

    public function test_presensi_ganda_ditolak(): void
    {
        $warga = $this->buatWarga();
        $kegiatan = $this->buatKegiatan();
        $kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true]);

        $this->expectException(QueryException::class);
        $kegiatan->kehadiran()->create(['nik' => $warga->nik, 'status_kehadiran' => true]);
    }

    public function test_detail_ikut_terhapus_saat_pemeriksaan_dihapus(): void
    {
        $p = $this->buatPemeriksaan($this->buatWarga(), $this->buatKegiatan());
        $p->pengukuranDasar()->create(['berat_badan' => 60]);
        $p->skriningTbc()->create([]);

        $p->delete();

        $this->assertDatabaseMissing('pengukuran_dasar', ['id_pemeriksaan' => $p->id_pemeriksaan]);
        $this->assertDatabaseMissing('skrining_tbc', ['id_pemeriksaan' => $p->id_pemeriksaan]);
    }

    public function test_koreksi_nik_ikut_memperbarui_tabel_terkait(): void
    {
        $warga = $this->buatWarga('3404000000000099');
        $p = $this->buatPemeriksaan($warga, $this->buatKegiatan());

        DB::table('warga')->where('nik', '3404000000000099')->update(['nik' => '3404000000000100']);

        $this->assertDatabaseHas('pemeriksaan', ['id_pemeriksaan' => $p->id_pemeriksaan, 'nik' => '3404000000000100']);
    }
}
