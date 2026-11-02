<?php

namespace Tests\Feature\Database;

use App\Models\Vaksin;
use App\Models\Vaksinasi;
use Database\Seeders\VaksinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Pengecekan kader 31 Okt 2026: Polio cukup dosis 1–3 (Polio 4 dihapus), tambah JE usia 10 bulan.
 */
class PerbaruiMasterVaksinTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function jalankanMigrasi(): void
    {
        (require database_path('migrations/2026_11_02_000003_perbarui_master_vaksin_polio_dan_je.php'))->up();
    }

    public function test_data_awal_tanpa_polio_4_dan_je_paling_akhir(): void
    {
        $this->seed(VaksinSeeder::class);

        $nama = Vaksin::orderBy('id_vaksin')->pluck('nama_vaksin')->all();
        $this->assertContains('Polio 3', $nama);
        $this->assertNotContains('Polio 4', $nama);
        $this->assertSame('JE (Japanese Encephalitis)', end($nama));
        $this->assertSame('Japanese Encephalitis, diberikan usia 10 bulan', Vaksin::where('nama_vaksin', 'JE (Japanese Encephalitis)')->value('deskripsi'));
    }

    public function test_migrasi_menghapus_polio_4_beserta_catatannya_dan_menambah_je(): void
    {
        $polio3 = Vaksin::create(['nama_vaksin' => 'Polio 3']);
        $polio4 = Vaksin::create(['nama_vaksin' => 'Polio 4']);
        $balita = $this->buatWarga('3404000000000001', ['tanggal_lahir' => '2025-06-01']);
        foreach ([$polio3, $polio4] as $vaksin) {
            Vaksinasi::create(['nik' => $balita->nik, 'id_vaksin' => $vaksin->id_vaksin, 'status_vaksinasi' => true, 'tanggal_vaksinasi' => '2025-10-01']);
        }

        $this->jalankanMigrasi();
        $this->jalankanMigrasi(); // aman dijalankan ulang: JE tidak dobel

        $this->assertSame(['Polio 3', 'JE (Japanese Encephalitis)'], Vaksin::orderBy('id_vaksin')->pluck('nama_vaksin')->all());
        $this->assertSame([$polio3->id_vaksin], Vaksinasi::pluck('id_vaksin')->all()); // catatan Polio 3 tetap
    }

    public function test_migrasi_tidak_mengisi_database_baru(): void
    {
        $this->jalankanMigrasi();

        $this->assertSame(0, Vaksin::count());
    }
}
