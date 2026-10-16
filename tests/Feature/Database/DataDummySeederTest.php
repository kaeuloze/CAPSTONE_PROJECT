<?php

namespace Tests\Feature\Database;

use App\Enums\StatusPemeriksaan;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use Database\Seeders\DataDummySeeder;
use Database\Seeders\PenyakitSeeder;
use Database\Seeders\VaksinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data dummy 250 warga + riwayat 6 bulan untuk uji coba di laptop pengembang.
 */
class DataDummySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_dummy_lengkap_dan_konsisten(): void
    {
        $this->seed([PenyakitSeeder::class, VaksinSeeder::class, DataDummySeeder::class]);

        $this->assertSame(250, Warga::count());
        foreach (['balita', 'remaja', 'dewasa', 'lansia'] as $kategori) {
            $this->assertGreaterThan(30, Warga::where('kategori_usia', $kategori)->count(), $kategori);
        }
        foreach (['hamil', 'nifas', 'menyusui'] as $kondisi) {
            $this->assertGreaterThan(0, Warga::where('status_kondisi', $kondisi)->count(), $kondisi);
        }

        $this->assertSame(6, Kegiatan::count());
        $this->assertSame(1, Kegiatan::where('status_kegiatan', true)->count());

        // Pemeriksaan hanya untuk warga yang hadir, bukan balita, semua kategori terisi.
        $this->assertSame(0, Pemeriksaan::query()
            ->leftJoin('kehadiran', fn ($j) => $j->on('kehadiran.nik', '=', 'pemeriksaan.nik')->on('kehadiran.id_kegiatan', '=', 'pemeriksaan.id_kegiatan'))
            ->where(fn ($q) => $q->whereNull('kehadiran.id_kehadiran')->orWhere('kehadiran.status_kehadiran', false))
            ->count());
        foreach (['remaja', 'dewasa', 'lansia', 'ibu_hamil', 'ibu_nifas'] as $kategori) {
            $this->assertGreaterThan(10, Pemeriksaan::selesai()->where('kategori_usia_saat_periksa', $kategori)->count(), $kategori);
        }
        // Yang di-Submit selalu mencatat jenis Rutin; draft hanya ada di kegiatan yang berlangsung.
        $this->assertSame(0, Pemeriksaan::selesai()->whereRaw('NOT FIND_IN_SET(?, jenis_pemeriksaan)', ['rutin'])->count());
        $this->assertSame(0, Pemeriksaan::draft()->whereHas('kegiatan', fn ($q) => $q->where('status_kegiatan', false))->count());
        $this->assertGreaterThan(0, Pemeriksaan::where('status_pemeriksaan', StatusPemeriksaan::Draft->value)->count());

        foreach (['skrining_sensorik', 'skrining_puma', 'skrining_aks', 'skrining_skilas', 'pemeriksaan_bumil', 'vaksinasi', 'asi_eksklusif', 'riwayat_ptm'] as $tabel) {
            $this->assertGreaterThan(0, DB::table($tabel)->count(), $tabel);
        }
        $this->assertSame(0, DB::table('asi_eksklusif')->where('bulan_ke', '>', 6)->count());
        // Vaksin dicatat pada kegiatan saat balita hadir.
        $this->assertSame(0, DB::table('vaksinasi as v')
            ->leftJoin('kehadiran as k', fn ($j) => $j->on('k.nik', '=', 'v.nik')->on('k.id_kegiatan', '=', 'v.id_kegiatan'))
            ->where(fn ($q) => $q->whereNull('k.id_kehadiran')->orWhere('k.status_kehadiran', false))->count());
    }
}
