<?php

namespace App\Services;

use App\Enums\KategoriUsia;
use App\Enums\StatusAsi;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\AsiEksklusif;
use App\Models\Kegiatan;
use App\Models\Vaksin;
use App\Models\Vaksinasi;
use App\Models\Warga;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Vaksinasi Balita & ASI Eksklusif (SKF-10; Figma 192:72 & 141:2678; Activity Diagram 2.19 & 2.20).
 *
 * - Daftar = balita yang HADIR pada kegiatan aktif (disetujui), dengan pencarian nama/NIK.
 * - "Progressive record": centang lama tetap tampil; kader mencentang dosis sesuai Buku KIA dan mengisi tanggalnya
 *   (bawaan tanggal kegiatan, bisa diubah sesuai Buku KIA — disetujui).
 * - ASI Eksklusif Bulan 0–6 (seperti SIGIZI Kesga, disetujui) hanya tampil selama usia balita belum lewat 6 bulan.
 * - Aman dipakai beberapa meja: hanya baris yang diubah kader itu yang disimpan; ditolak bila baris yang sama
 *   baru saja diubah kader lain.
 */
class VaksinasiService
{
    /** Balita hadir pada kegiatan aktif. */
    public function daftar(Kegiatan $kegiatan, ?string $cari): LengthAwarePaginator
    {
        return Warga::query()
            ->join('kehadiran as k', function (JoinClause $join) use ($kegiatan) {
                $join->on('k.nik', '=', 'warga.nik')
                    ->where('k.id_kegiatan', '=', $kegiatan->id_kegiatan)
                    ->where('k.status_kehadiran', '=', true);
            })
            ->kategori(KategoriUsia::Balita, $kegiatan->tanggal_kegiatan)
            ->cari($cari)
            ->select(['warga.nik', 'warga.nama_warga', 'warga.tanggal_lahir', 'warga.jenis_kelamin'])
            ->orderBy('warga.nama_warga')
            ->orderBy('warga.nik')
            ->paginate(config('posyandu.vaksinasi.per_halaman'))
            ->withQueryString();
    }

    public function hadir(Kegiatan $kegiatan, Warga $warga): bool
    {
        return $kegiatan->kehadiran()->where('nik', $warga->nik)->where('status_kehadiran', true)->exists();
    }

    public function balita(Warga $warga, CarbonInterface $tanggal): bool
    {
        return KategoriUsia::dariTanggalLahir($warga->tanggal_lahir, $tanggal) === KategoriUsia::Balita;
    }

    public function usiaBulan(Warga $warga, CarbonInterface $tanggal): int
    {
        return (int) $warga->tanggal_lahir->diffInMonths($tanggal);
    }

    /** Bagian ASI tampil selama usia belum lewat 6 bulan (proposal Gambar 2.60). */
    public function tampilAsi(Warga $warga, CarbonInterface $tanggal): bool
    {
        return $this->usiaBulan($warga, $tanggal) <= (int) config('posyandu.vaksinasi.asi_bulan_maks');
    }

    /** @return array<int, int> bulan ASI yang dicatat (0 … 6) */
    public function bulanAsi(): array
    {
        return range(0, (int) config('posyandu.vaksinasi.asi_bulan_maks'));
    }

    /** @return Collection<int, Vaksin> checklist vaksin sesuai urutan Master Vaksin */
    public function daftarVaksin(): Collection
    {
        return Vaksin::query()->orderBy('id_vaksin')->get();
    }

    /** @return array{0: Collection<int, Vaksinasi>, 1: Collection<int, AsiEksklusif>} catatan tersimpan per id_vaksin / bulan_ke */
    public function catatan(Warga $warga): array
    {
        return [
            Vaksinasi::query()->where('nik', $warga->nik)->get()->keyBy('id_vaksin'),
            AsiEksklusif::query()->where('nik', $warga->nik)->get()->keyBy('bulan_ke'),
        ];
    }

    /** Sidik nilai satu baris vaksin, mis. "1|2026-04-02" atau "0|" (untuk deteksi perubahan antar-meja). */
    public static function sidikVaksin(?Vaksinasi $v): string
    {
        return $v && $v->status_vaksinasi ? '1|'.$v->tanggal_vaksinasi?->toDateString() : '0|';
    }

    public static function sidikAsi(?AsiEksklusif $a): string
    {
        return $a?->status_asi?->value ?? '';
    }

    /**
     * Tombol "Simpan".
     *
     * @param  array<int|string, array{sudah?: mixed, tanggal?: ?string}>  $vaksin  isian per id_vaksin
     * @param  array<int|string, ?string>  $asi  isian per bulan_ke (ya / tidak / tidak_recall / kosong)
     * @param  array{vaksin?: array, asi?: array}  $awal  sidik nilai saat form dibuka
     * @return int jumlah baris yang berubah
     *
     * @throws AksiDitolakException|DataSudahBerubahException
     */
    public function simpan(Kegiatan $kegiatan, Warga $warga, array $vaksin, array $asi, array $awal): int
    {
        return DB::transaction(function () use ($kegiatan, $warga, $vaksin, $asi, $awal) {
            $kegiatan = Kegiatan::query()->whereKey($kegiatan->getKey())->lockForUpdate()->firstOrFail();
            if (! $kegiatan->status_kegiatan) {
                throw new AksiDitolakException('Kegiatan sudah selesai sehingga catatan vaksinasi tidak bisa disimpan.');
            }
            if (! $this->hadir($kegiatan, $warga) || ! $this->balita($warga, $kegiatan->tanggal_kegiatan)) {
                throw new AksiDitolakException("{$warga->nama_warga} bukan balita yang tercatat Hadir pada kegiatan ini.");
            }

            $berubah = 0;
            $konflik = [];

            foreach ($this->daftarVaksin() as $v) {
                $isian = $vaksin[$v->id_vaksin] ?? null;
                if ($isian === null) {
                    continue;
                }
                $sudah = filter_var($isian['sudah'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $baru = $sudah ? '1|'.($isian['tanggal'] ?? '') : '0|';
                $sidikAwal = (string) ($awal['vaksin'][$v->id_vaksin] ?? '0|');
                if ($baru === $sidikAwal) {
                    continue; // tidak diubah kader ini
                }

                $baris = Vaksinasi::query()->where('nik', $warga->nik)->where('id_vaksin', $v->id_vaksin)->lockForUpdate()->first();
                if (self::sidikVaksin($baris) !== $sidikAwal) {
                    $konflik[] = $v->nama_vaksin;

                    continue;
                }

                if ($sudah) {
                    $data = ['status_vaksinasi' => true, 'tanggal_vaksinasi' => $isian['tanggal'], 'id_kegiatan' => $kegiatan->id_kegiatan];
                    $baris ? $baris->update($data) : Vaksinasi::create($data + ['nik' => $warga->nik, 'id_vaksin' => $v->id_vaksin]);
                } else {
                    $baris?->delete(); // centang dibatalkan (salah centang)
                }
                $berubah++;
            }

            if ($this->tampilAsi($warga, $kegiatan->tanggal_kegiatan)) {
                foreach ($this->bulanAsi() as $bulan) {
                    if (! array_key_exists($bulan, $asi)) {
                        continue;
                    }
                    $baru = (string) ($asi[$bulan] ?? '');
                    $sidikAwal = (string) ($awal['asi'][$bulan] ?? '');
                    if ($baru === $sidikAwal) {
                        continue;
                    }

                    $baris = AsiEksklusif::query()->where('nik', $warga->nik)->where('bulan_ke', $bulan)->lockForUpdate()->first();
                    if (self::sidikAsi($baris) !== $sidikAwal) {
                        $konflik[] = "ASI Bulan {$bulan}";

                        continue;
                    }

                    if ($baru === '') {
                        $baris?->delete();
                    } else {
                        $data = ['status_asi' => StatusAsi::from($baru), 'tanggal_dicatat' => $kegiatan->tanggal_kegiatan->toDateString()];
                        $baris ? $baris->update($data) : AsiEksklusif::create($data + ['nik' => $warga->nik, 'bulan_ke' => $bulan]);
                    }
                    $berubah++;
                }
            }

            if ($konflik !== []) {
                throw new DataSudahBerubahException(
                    'Catatan "'.implode('", "', $konflik).'" baru saja diubah oleh kader lain. Muat ulang halaman untuk melihat data terbaru, lalu ulangi perubahan Anda.'
                );
            }

            return $berubah;
        });
    }

    /**
     * Catatan vaksin & ASI untuk Riwayat Pemeriksaan, dikelompokkan per tanggal kunjungan (disetujui):
     * vaksin menurut tanggal kegiatan saat dicatat, ASI menurut tanggal dicatat.
     *
     * @return array<int, array{tanggal: CarbonInterface, kegiatan: ?string, vaksin: array<int, string>, asi: array<int, string>}>
     */
    public function riwayat(Warga $warga): array
    {
        $kelompok = [];
        $vaksinasi = Vaksinasi::query()->with(['vaksin', 'kegiatan'])->where('nik', $warga->nik)->where('status_vaksinasi', true)->get();
        foreach ($vaksinasi as $v) {
            $tanggal = $v->kegiatan?->tanggal_kegiatan ?? $v->tanggal_vaksinasi;
            $kunci = $tanggal?->toDateString() ?? '0000-00-00';
            $kelompok[$kunci] ??= ['tanggal' => $tanggal, 'kegiatan' => $v->kegiatan?->nama_kegiatan, 'vaksin' => [], 'asi' => []];
            $kelompok[$kunci]['vaksin'][] = $v->vaksin->nama_vaksin.($v->tanggal_vaksinasi ? ' ('.$v->tanggal_vaksinasi->translatedFormat('j M Y').')' : '');
        }
        foreach (AsiEksklusif::query()->where('nik', $warga->nik)->orderBy('bulan_ke')->get() as $a) {
            $kunci = $a->tanggal_dicatat->toDateString();
            $kelompok[$kunci] ??= ['tanggal' => $a->tanggal_dicatat, 'kegiatan' => null, 'vaksin' => [], 'asi' => []];
            $kelompok[$kunci]['asi'][] = "Bulan {$a->bulan_ke}: ".$a->status_asi->label();
        }
        krsort($kelompok);

        return array_values($kelompok);
    }
}
