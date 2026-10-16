<?php

namespace App\Services;

use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Warga;
use Illuminate\Support\Facades\DB;

/**
 * Logika bisnis Data Warga (SKF-05): simpan, ubah, dan hapus data induk warga
 * beserta Riwayat PTM-nya. Dipisah dari controller agar bisa dipakai ulang dan diuji.
 */
class WargaService
{
    /**
     * Menyimpan warga baru beserta riwayat PTM (Activity Diagram 2.30).
     *
     * @param  array<string, mixed>  $data  data identitas yang sudah divalidasi
     * @param  array<int, int>  $idPenyakit  id penyakit yang dicentang
     */
    public function tambah(array $data, array $idPenyakit): Warga
    {
        return DB::transaction(function () use ($data, $idPenyakit) {
            $warga = Warga::create($data); // kategori usia dihitung otomatis oleh model
            $this->sinkronPenyakit($warga, $idPenyakit);

            return $warga;
        });
    }

    /**
     * Mengubah data warga (Activity Diagram 2.31).
     *
     * @param  string  $versi  nilai updated_at saat formulir dibuka; jika berbeda dengan
     *                         database berarti kader lain sudah mengubah data ini lebih dulu
     *
     * @throws DataSudahBerubahException
     */
    public function ubah(Warga $warga, array $data, array $idPenyakit, string $versi): Warga
    {
        return DB::transaction(function () use ($warga, $data, $idPenyakit, $versi) {
            // Kunci baris agar dua penyimpanan bersamaan diproses bergantian.
            $terkini = Warga::query()->whereKey($warga->getKey())->lockForUpdate()->first();

            if ($terkini === null || $terkini->updated_at?->format('Y-m-d H:i:s') !== $versi) {
                throw new DataSudahBerubahException;
            }

            $terkini->fill($data);
            $identitasBerubah = $terkini->isDirty();
            $terkini->save(); // NIK baru ikut memperbarui tabel terkait (ON UPDATE CASCADE)

            $penyakitBerubah = $this->sinkronPenyakit($terkini, $idPenyakit);
            if (! $identitasBerubah && $penyakitBerubah) {
                $terkini->touch(); // tandai sebagai versi baru untuk pengaman edit bersamaan
            }

            return $terkini;
        });
    }

    /**
     * Menghapus warga beserta riwayat PTM-nya (Activity Diagram 2.32).
     * Ditolak jika warga pernah hadir/diperiksa/divaksin/punya data ASI (keputusan 17 & 28).
     * Catatan "Tidak Hadir" ikut terhapus.
     *
     * @throws DataMasihDipakaiException
     */
    public function hapus(Warga $warga): void
    {
        DB::transaction(function () use ($warga) {
            if ($warga->sudahDipakai()) {
                throw new DataMasihDipakaiException(
                    "Data {$warga->nama_warga} tidak bisa dihapus karena sudah pernah hadir, diperiksa, divaksin, atau memiliki data ASI."
                );
            }

            $warga->riwayatPtm()->delete(); // riwayat PTM adalah bagian dari data induk warga
            $warga->kehadiran()->where('status_kehadiran', false)->delete(); // catatan "Tidak Hadir"
            $warga->delete();
        });
    }

    /**
     * Menyamakan riwayat PTM dengan centang di formulir. Penyakit yang sudah tercatat
     * tetap memakai tanggal_dicatat lamanya; penyakit baru dicatat dengan tanggal hari ini.
     *
     * @return bool true jika ada yang ditambah atau dihapus
     */
    private function sinkronPenyakit(Warga $warga, array $idPenyakit): bool
    {
        $baru = array_values(array_unique(array_map('intval', $idPenyakit)));
        $lama = $warga->riwayatPtm()->pluck('id_penyakit')->map(fn ($id) => (int) $id)->all();

        $dihapus = array_diff($lama, $baru);
        $ditambah = array_diff($baru, $lama);

        if ($dihapus !== []) {
            $warga->riwayatPtm()->whereIn('id_penyakit', $dihapus)->delete();
        }
        foreach ($ditambah as $id) {
            // sumber_riwayat memakai nilai bawaan database (kartu_bantu), keputusan 14.
            $warga->riwayatPtm()->create(['id_penyakit' => $id, 'tanggal_dicatat' => now()->toDateString()]);
        }

        return $dihapus !== [] || $ditambah !== [];
    }
}
