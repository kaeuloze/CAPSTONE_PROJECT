<?php

namespace App\Services\Pemeriksaan;

use App\Enums\KategoriPemeriksaan;
use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use App\Enums\StatusPemeriksaan;
use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Models\Kegiatan;
use App\Models\Pemeriksaan;
use App\Models\Warga;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis menu Pemeriksaan (SKF-07/08; Figma "Pemeriksaan — Tanpa Kolom Status" & form "hasil rev").
 *
 * - Daftar hanya berisi warga yang HADIR pada kegiatan aktif, bukan balita, dan belum di-Submit.
 * - Form dipilih otomatis: hamil → Ibu Hamil; nifas/menyusui → Ibu Nifas; selain itu sesuai kategori usia.
 * - Data tercatat saat Simpan pertama (bukan saat form dibuka).
 * - Simpan per bagian: hanya bagian yang diubah kader itu yang disimpan; ditolak bila bagian yang sama
 *   sudah diubah kader lain sejak form dibuka.
 * - Submit: semua isian wajib (*) harus terisi; status menjadi selesai dan warga pindah ke Riwayat.
 */
class PemeriksaanService
{
    /** Daftar antrean pemeriksaan pada kegiatan aktif, dengan pencarian & filter kategori form. */
    public function antrean(Kegiatan $kegiatan, ?string $cari, ?KategoriPemeriksaan $kategori): LengthAwarePaginator
    {
        [$sqlKategori, $ikatan] = $this->sqlKategori($kegiatan->tanggal_kegiatan);

        return Warga::query()
            ->join('kehadiran as k', function (JoinClause $join) use ($kegiatan) {
                $join->on('k.nik', '=', 'warga.nik')
                    ->where('k.id_kegiatan', '=', $kegiatan->id_kegiatan)
                    ->where('k.status_kehadiran', '=', true);
            })
            ->leftJoin('pemeriksaan as p', function (JoinClause $join) use ($kegiatan) {
                $join->on('p.nik', '=', 'warga.nik')->where('p.id_kegiatan', '=', $kegiatan->id_kegiatan);
            })
            ->where(fn (Builder $q) => $q->whereNull('p.id_pemeriksaan')
                ->orWhere('p.status_pemeriksaan', StatusPemeriksaan::Draft->value))
            ->whereRaw("COALESCE(p.kategori_usia_saat_periksa, {$sqlKategori}) <> 'balita'", $ikatan)
            ->when($kategori, fn ($q) => $q->whereRaw("COALESCE(p.kategori_usia_saat_periksa, {$sqlKategori}) = ?", [...$ikatan, $kategori->value]))
            ->cari($cari)
            ->select(['warga.nik', 'warga.nama_warga', 'warga.tanggal_lahir'])
            ->selectRaw("COALESCE(p.kategori_usia_saat_periksa, {$sqlKategori}) as kategori_form", $ikatan)
            ->selectRaw('p.status_pemeriksaan as status_pemeriksaan')
            ->orderBy('warga.nama_warga')
            ->orderBy('warga.nik')
            ->paginate(config('posyandu.presensi.per_halaman'))
            ->withQueryString();
    }

    /** Kategori form untuk warga pada tanggal kegiatan; null bila balita (tidak diperiksa di menu ini). */
    public function kategoriUntuk(Warga $warga, CarbonInterface $tanggal): ?KategoriPemeriksaan
    {
        return match ($warga->status_kondisi) {
            StatusKondisi::Hamil => KategoriPemeriksaan::IbuHamil,
            StatusKondisi::Nifas, StatusKondisi::Menyusui => KategoriPemeriksaan::IbuNifas, // keputusan 39
            default => match (KategoriUsia::dariTanggalLahir($warga->tanggal_lahir, $tanggal)) {
                KategoriUsia::Balita => null,
                KategoriUsia::Remaja => KategoriPemeriksaan::Remaja,
                KategoriUsia::Dewasa => KategoriPemeriksaan::Dewasa,
                KategoriUsia::Lansia => KategoriPemeriksaan::Lansia,
            },
        };
    }

    /** True bila warga tercatat HADIR pada kegiatan. */
    public function hadir(Kegiatan $kegiatan, Warga $warga): bool
    {
        return $kegiatan->kehadiran()->where('nik', $warga->nik)->where('status_kehadiran', true)->exists();
    }

    public function pemeriksaanDari(Kegiatan $kegiatan, Warga $warga): ?Pemeriksaan
    {
        return Pemeriksaan::query()->where('id_kegiatan', $kegiatan->id_kegiatan)->where('nik', $warga->nik)->first();
    }

    /**
     * Menyimpan isian formulir (Simpan = draft, Submit = selesai).
     *
     * @param  array<string, array<string, mixed>>  $isian  isian per bagian: [kunci => [kolom => nilai]]
     * @param  array<string, string>  $versi  versi tiap bagian saat form dibuka
     * @param  array<int, string>|null  $diubah  bagian yang diubah kader (null = periksa semua bagian)
     *
     * @throws AksiDitolakException|DataSudahBerubahException|ValidationException
     */
    public function simpan(Kegiatan $kegiatan, Warga $warga, DefinisiForm $form, array $isian, array $versi, ?array $diubah, bool $submit): Pemeriksaan
    {
        return DB::transaction(function () use ($kegiatan, $warga, $form, $isian, $versi, $diubah, $submit) {
            $kegiatan = Kegiatan::query()->whereKey($kegiatan->getKey())->lockForUpdate()->firstOrFail();
            if (! $kegiatan->status_kegiatan) {
                throw new AksiDitolakException('Kegiatan sudah selesai sehingga pemeriksaan tidak bisa disimpan. Buka kembali kegiatan dari Arsip untuk melanjutkan.');
            }
            if (! $this->hadir($kegiatan, $warga)) {
                throw new AksiDitolakException("{$warga->nama_warga} belum tercatat Hadir pada kegiatan ini.");
            }

            $pemeriksaan = $this->ambilAtauBuat($kegiatan, $warga, $form);
            if ($pemeriksaan->status_pemeriksaan === StatusPemeriksaan::Selesai) {
                throw new AksiDitolakException("Pemeriksaan {$warga->nama_warga} sudah di-Submit. Koreksi dilakukan di menu Riwayat Pemeriksaan.");
            }

            $konflik = [];
            foreach ($form->bagian() as $bagian) {
                if ($diubah !== null && ! in_array($bagian->kunci, $diubah, true)) {
                    continue; // bagian ini tidak disentuh kader → biarkan isian meja lain
                }

                $baris = $pemeriksaan->{$bagian->relasi}()->lockForUpdate()->first();
                $baru = $bagian->nilaiIsian($isian[$bagian->kunci] ?? []);
                if ($baru === $bagian->nilaiTersimpan($baris)) {
                    continue; // tidak ada perubahan
                }
                if (($versi[$bagian->kunci] ?? null) !== $bagian->versi($baris)) {
                    $konflik[] = $bagian->judul;

                    continue;
                }

                $data = $baru + ($bagian->hitung ? ($bagian->hitung)($baru, $warga, $kegiatan) : []);
                $baris ? $baris->update($data) : $pemeriksaan->{$bagian->relasi}()->create($data);
            }

            if ($konflik !== []) {
                throw new DataSudahBerubahException(
                    'Bagian "'.implode('", "', $konflik).'" baru saja diubah oleh kader lain. Muat ulang halaman untuk melihat data terbaru, lalu ulangi perubahan Anda.'
                );
            }

            if ($submit) {
                $this->submit($pemeriksaan, $form);
            } else {
                $pemeriksaan->touch();
            }

            return $pemeriksaan->refresh();
        });
    }

    /** Mengecek isian wajib (gabungan isian semua meja) lalu menandai pemeriksaan selesai. */
    private function submit(Pemeriksaan $pemeriksaan, DefinisiForm $form): void
    {
        $kurang = [];
        $jenis = [];
        foreach ($form->bagian() as $bagian) {
            $nilai = $bagian->nilaiTersimpan($pemeriksaan->{$bagian->relasi}()->first());
            foreach ($bagian->wajibKosong($nilai) as $kolom) {
                $kurang["bagian.{$bagian->kunci}.{$kolom}"] = 'Wajib diisi sebelum Submit.';
            }
            if ($bagian->terisi($nilai)) {
                $jenis[$bagian->jenis->value] = $bagian->jenis;
            }
        }

        if ($kurang !== []) {
            throw ValidationException::withMessages(
                ['submit' => 'Submit belum bisa dilakukan: masih ada isian bertanda * yang kosong.'] + $kurang
            );
        }

        $pemeriksaan->update([
            'status_pemeriksaan' => StatusPemeriksaan::Selesai,
            'jenis_pemeriksaan' => array_values($jenis),
        ]);
    }

    private function ambilAtauBuat(Kegiatan $kegiatan, Warga $warga, DefinisiForm $form): Pemeriksaan
    {
        $cari = fn () => Pemeriksaan::query()
            ->where('id_kegiatan', $kegiatan->id_kegiatan)
            ->where('nik', $warga->nik)
            ->lockForUpdate()
            ->first();

        $pemeriksaan = $cari();
        if ($pemeriksaan) {
            return $pemeriksaan;
        }

        try {
            return DB::transaction(fn () => Pemeriksaan::create([
                'nik' => $warga->nik,
                'id_kegiatan' => $kegiatan->id_kegiatan,
                'kategori_usia_saat_periksa' => $form->kategori(),
                'tanggal_pemeriksaan' => $kegiatan->tanggal_kegiatan->toDateString(),
                'status_pemeriksaan' => StatusPemeriksaan::Draft,
            ]));
        } catch (UniqueConstraintViolationException) {
            return $cari(); // kader lain baru saja membuat pemeriksaan yang sama
        }
    }

    /**
     * Ekspresi SQL kategori form (sama dengan kategoriUntuk) untuk daftar & filter.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private function sqlKategori(CarbonInterface $tanggal): array
    {
        $sampai = fn (KategoriUsia $k) => $k->rentangTanggalLahir($tanggal)['sampai'];

        return [
            "(CASE
                WHEN warga.status_kondisi = 'hamil' THEN 'ibu_hamil'
                WHEN warga.status_kondisi IN ('nifas', 'menyusui') THEN 'ibu_nifas'
                WHEN warga.tanggal_lahir <= ? THEN 'lansia'
                WHEN warga.tanggal_lahir <= ? THEN 'dewasa'
                WHEN warga.tanggal_lahir <= ? THEN 'remaja'
                ELSE 'balita'
             END)",
            [$sampai(KategoriUsia::Lansia), $sampai(KategoriUsia::Dewasa), $sampai(KategoriUsia::Remaja)],
        ];
    }
}
