<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Master Vaksin sesuai pengecekan bersama kader (31 Okt 2026): Polio cukup dosis 1–3, sehingga
 * "Polio 4" dihapus beserta catatan vaksinasinya (disetujui pengguna; di database utama hanya data
 * dummy), dan ditambah "JE (Japanese Encephalitis)" untuk usia 10 bulan.
 *
 * Hanya berlaku untuk database yang master vaksinnya sudah terisi. Database baru (migrate:fresh)
 * dibiarkan kosong agar VaksinSeeder mengisi semua vaksin dengan urutan yang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('vaksin')->exists()) {
            return;
        }

        DB::transaction(function () {
            $polio4 = DB::table('vaksin')->where('nama_vaksin', 'Polio 4')->value('id_vaksin');
            if ($polio4 !== null) {
                DB::table('vaksinasi')->where('id_vaksin', $polio4)->delete();
                DB::table('vaksin')->where('id_vaksin', $polio4)->delete();
            }

            if (! DB::table('vaksin')->where('nama_vaksin', 'JE (Japanese Encephalitis)')->exists()) {
                DB::table('vaksin')->insert([
                    'nama_vaksin' => 'JE (Japanese Encephalitis)',
                    'deskripsi' => 'Japanese Encephalitis, diberikan usia 10 bulan',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    /** Tidak dibatalkan: catatan vaksinasi Polio 4 yang terhapus tidak dapat dikembalikan. */
    public function down(): void
    {
    }
};
