<?php

namespace App\Services;

use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Models\Warga;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Logika bisnis Kegiatan & Presensi (SKF-06; Activity Diagram 2.5–2.9; keputusan revisi 7).
 *
 * Aturan utama:
 * - Hanya SATU kegiatan boleh berstatus Berlangsung (semua meja bekerja pada kegiatan yang sama).
 * - Kegiatan otomatis selesai setelah 24 jam sejak dibuat / dibuka kembali.
 * - Saat kegiatan selesai, warga yang belum hadir dicatat "Tidak Hadir".
 * - Presensi tersimpan setiap klik dan bersifat "set" (aman bila dua kader menekan bersamaan).
 */
class KegiatanService
{
    /** Kunci bersama agar dua kader tidak membuat/membuka kegiatan pada saat yang sama. */
    private const KUNCI = 'posyandu:kegiatan-berlangsung';

    /**
     * Menutup kegiatan berlangsung yang sudah melewati batas waktu.
     * Dipanggil setiap halaman Kegiatan/Presensi dibuka dan oleh tugas terjadwal.
     *
     * @return int jumlah kegiatan yang ditutup
     */
    public function tutupYangKedaluwarsa(): int
    {
        $batas = now()->subHours((int) config('posyandu.kegiatan.durasi_jam'));
        $daftar = Kegiatan::query()->berlangsung()->where('waktu_dibuka', '<=', $batas)->get();

        foreach ($daftar as $kegiatan) {
            $this->tandaiSelesai($kegiatan);
        }

        return $daftar->count();
    }

    /** Kegiatan yang sedang berlangsung (setelah penutupan otomatis diperiksa), atau null. */
    public function kegiatanAktif(): ?Kegiatan
    {
        $this->tutupYangKedaluwarsa();

        return Kegiatan::query()->berlangsung()->latest('waktu_dibuka')->first();
    }

    /**
     * Membuat dan langsung memulai kegiatan (Activity Diagram 2.6, tombol "Buat & Mulai Kegiatan").
     *
     * @throws AksiDitolakException jika masih ada kegiatan lain yang berlangsung
     */
    public function buat(array $data): Kegiatan
    {
        return Cache::lock(self::KUNCI, 10)->block(5, function () use ($data) {
            $this->pastikanTidakAdaYangBerlangsung();

            return Kegiatan::create([
                'nama_kegiatan' => $data['nama_kegiatan'],
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'status_kegiatan' => true,
                'waktu_dibuka' => now(),
            ]);
        });
    }

    /**
     * Mengubah nama, tanggal, dan status kegiatan (Activity Diagram 2.8).
     * Status → Berlangsung = dibuka kembali (hitungan 24 jam diulang).
     * Status → Selesai = warga yang belum hadir dicatat Tidak Hadir.
     *
     * @throws DataSudahBerubahException|AksiDitolakException
     */
    public function ubah(Kegiatan $kegiatan, array $data, string $versi): Kegiatan
    {
        return Cache::lock(self::KUNCI, 10)->block(5, function () use ($kegiatan, $data, $versi) {
            return DB::transaction(function () use ($kegiatan, $data, $versi) {
                $terkini = Kegiatan::query()->whereKey($kegiatan->getKey())->lockForUpdate()->first();
                if ($terkini === null || $terkini->updated_at?->format('Y-m-d H:i:s') !== $versi) {
                    throw new DataSudahBerubahException;
                }

                $statusBaru = (bool) $data['status_kegiatan'];
                $dibukaKembali = $statusBaru && ! $terkini->status_kegiatan;
                $diselesaikan = ! $statusBaru && $terkini->status_kegiatan;

                if ($dibukaKembali) {
                    $this->pastikanTidakAdaYangBerlangsung($terkini);
                    $terkini->waktu_dibuka = now();
                    $terkini->status_kegiatan = true;
                }

                $terkini->nama_kegiatan = $data['nama_kegiatan'];
                $terkini->tanggal_kegiatan = $data['tanggal_kegiatan'];
                $terkini->save();

                if ($diselesaikan) {
                    $this->tandaiSelesai($terkini);
                }

                return $terkini->refresh();
            });
        });
    }

    /** Tombol "Selesaikan Kegiatan" di halaman Presensi (revisi). */
    public function selesaikan(Kegiatan $kegiatan): void
    {
        if (! $kegiatan->status_kegiatan) {
            return; // sudah selesai (mungkin oleh kader lain) — tidak perlu apa-apa
        }

        $this->tandaiSelesai($kegiatan);
    }

    /**
     * Menghapus kegiatan (Activity Diagram 2.9). Ditolak bila sudah ada warga yang hadir,
     * diperiksa, atau divaksin pada kegiatan ini. Catatan "Tidak Hadir" ikut terhapus.
     *
     * @throws DataMasihDipakaiException
     */
    public function hapus(Kegiatan $kegiatan): void
    {
        DB::transaction(function () use ($kegiatan) {
            $dipakai = $kegiatan->kehadiran()->where('status_kehadiran', true)->exists()
                || $kegiatan->pemeriksaan()->exists()
                || $kegiatan->vaksinasi()->exists();

            if ($dipakai) {
                throw new DataMasihDipakaiException(
                    "Kegiatan {$kegiatan->nama_kegiatan} tidak bisa dihapus karena sudah ada warga yang hadir, diperiksa, atau divaksin."
                );
            }

            $kegiatan->kehadiran()->delete();
            $kegiatan->delete();
        });
    }

    /**
     * Menandai warga Hadir / membatalkan (wireframe 2.42: Tandai Hadir / Batalkan).
     * Bersifat "set": menekan Tandai Hadir dua kali tetap Hadir.
     *
     * @throws AksiDitolakException
     */
    public function aturKehadiran(Kegiatan $kegiatan, Warga $warga, bool $hadir): Kehadiran
    {
        $this->tutupYangKedaluwarsa();

        return DB::transaction(function () use ($kegiatan, $warga, $hadir) {
            $kegiatan = Kegiatan::query()->whereKey($kegiatan->getKey())->lockForUpdate()->firstOrFail();
            if (! $kegiatan->status_kegiatan) {
                throw new AksiDitolakException('Kegiatan ini sudah selesai sehingga presensi tidak bisa diubah lagi.');
            }

            if (! $hadir && $warga->pemeriksaan()->where('id_kegiatan', $kegiatan->id_kegiatan)->exists()) {
                throw new AksiDitolakException("Kehadiran {$warga->nama_warga} tidak bisa dibatalkan karena sudah mulai/selesai diperiksa.");
            }
            if (! $hadir && (
                $warga->vaksinasi()->where('id_kegiatan', $kegiatan->id_kegiatan)->exists()
                || $warga->asiEksklusif()->whereDate('tanggal_dicatat', $kegiatan->tanggal_kegiatan)->exists()
            )) {
                throw new AksiDitolakException("Kehadiran {$warga->nama_warga} tidak bisa dibatalkan karena sudah ada catatan vaksinasi/ASI pada kegiatan ini.");
            }

            $kehadiran = Kehadiran::query()
                ->where('id_kegiatan', $kegiatan->id_kegiatan)
                ->where('nik', $warga->nik)
                ->lockForUpdate()
                ->first() ?? new Kehadiran(['id_kegiatan' => $kegiatan->id_kegiatan, 'nik' => $warga->nik]);

            if ($hadir && ! $kehadiran->status_kehadiran) {
                $kehadiran->waktu_kehadiran = now(); // jam pertama kali ditandai hadir
            }
            if (! $hadir) {
                $kehadiran->waktu_kehadiran = null;
            }
            $kehadiran->status_kehadiran = $hadir;
            $kehadiran->save();

            return $kehadiran;
        });
    }

    /** Mengubah status menjadi Selesai lalu mencatat "Tidak Hadir" untuk warga yang belum hadir (SKF-06). */
    private function tandaiSelesai(Kegiatan $kegiatan): void
    {
        DB::transaction(function () use ($kegiatan) {
            Kegiatan::query()->whereKey($kegiatan->getKey())->update(['status_kegiatan' => false, 'updated_at' => now()]);

            $sekarang = now()->toDateTimeString();
            $belumTercatat = Warga::query()
                ->select('nik')
                ->selectRaw('? as id_kegiatan, 0 as status_kehadiran, ? as created_at, ? as updated_at', [$kegiatan->id_kegiatan, $sekarang, $sekarang])
                ->whereNotIn('nik', Kehadiran::query()->select('nik')->where('id_kegiatan', $kegiatan->id_kegiatan));

            Kehadiran::query()->insertUsing(['nik', 'id_kegiatan', 'status_kehadiran', 'created_at', 'updated_at'], $belumTercatat);
        });

        $kegiatan->refresh();
    }

    /** @throws AksiDitolakException */
    private function pastikanTidakAdaYangBerlangsung(?Kegiatan $kecuali = null): void
    {
        $this->tutupYangKedaluwarsa();

        $lain = Kegiatan::query()->berlangsung()
            ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->getKey()))
            ->first();

        if ($lain) {
            throw new AksiDitolakException(
                "Masih ada kegiatan yang berlangsung ({$lain->nama_kegiatan}). Selesaikan kegiatan tersebut terlebih dahulu."
            );
        }
    }
}
