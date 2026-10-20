<?php

namespace App\Services\Rekapitulasi;

use App\Enums\KategoriRekap;
use App\Models\Kegiatan;
use App\Models\Rekapitulasi;
use App\Models\RekapitulasiDetail;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Menu Rekapitulasi (SKF-12; Activity Diagram 2.33–2.35; Figma 306:33 hub, 306:103, 316:55, 329:79).
 * Angka dihitung ulang setiap kali halaman dibuka / Excel diunduh, lalu disimpan ke
 * REKAPITULASI & REKAPITULASI_DETAIL (disetujui 21 Okt 2026).
 */
class RekapitulasiService
{
    public function __construct(private readonly PenghitungRekap $penghitung)
    {
    }

    /** Bulan kegiatan posyandu terakhir (Activity Diagram: "menampilkan data dari kegiatan terakhir"). */
    public function periodeBawaan(): Periode
    {
        return Periode::dariTanggal($this->tanggalKegiatanTerakhir() ?? CarbonImmutable::today());
    }

    /** Tanggal kegiatan posyandu terakhir (hari ini atau sebelumnya), null bila belum ada. */
    public function tanggalKegiatanTerakhir(): ?CarbonImmutable
    {
        $terakhir = Kegiatan::query()
            ->where('tanggal_kegiatan', '<=', CarbonImmutable::today()->toDateString())
            ->max('tanggal_kegiatan');

        return $terakhir ? CarbonImmutable::parse($terakhir) : null;
    }

    /** Pilihan tahun di filter: tahun kegiatan pertama s.d. tahun ini (terbaru dulu). */
    public function daftarTahun(): array
    {
        $tahunIni = (int) CarbonImmutable::today()->format('Y');
        $pertama = Kegiatan::query()->min('tanggal_kegiatan');
        $mulai = $pertama ? min((int) CarbonImmutable::parse($pertama)->format('Y'), $tahunIni) : $tahunIni;

        return range($tahunIni, $mulai);
    }

    /**
     * Menghitung rekap, menyimpannya, dan mengembalikan data untuk tampilan/Excel.
     *
     * @return array{kategori: KategoriRekap, periode: Periode, item: list<ItemRekap>, kegiatan: \Illuminate\Support\Collection, acuan: CarbonImmutable, rekapitulasi: Rekapitulasi}
     */
    public function susun(KategoriRekap $kategori, Periode $periode): array
    {
        $item = $this->penghitung->hitung($kategori, $periode);
        $kegiatan = $this->penghitung->kegiatanDalam($periode);

        return [
            'kategori' => $kategori,
            'periode' => $periode,
            'item' => $item,
            'kegiatan' => $kegiatan,
            'acuan' => $this->penghitung->tanggalAcuan($periode),
            'rekapitulasi' => $this->simpan($kategori, $periode, $item, $kegiatan),
        ];
    }

    /** @param list<ItemRekap> $item */
    private function simpan(KategoriRekap $kategori, Periode $periode, array $item, $kegiatan): Rekapitulasi
    {
        $catatan = $kegiatan->isEmpty()
            ? 'Tidak ada kegiatan posyandu pada periode ini.'
            : 'Dihitung otomatis dari kegiatan: '.$kegiatan
                ->map(fn ($k) => $k->nama_kegiatan.' ('.CarbonImmutable::parse($k->tanggal_kegiatan)->translatedFormat('j F Y').')')
                ->implode(', ').'.';

        return DB::transaction(function () use ($kategori, $periode, $item, $catatan) {
            // createOrFirst aman bila dua kader membuka rekap yang sama bersamaan (UNIQUE kategori+periode).
            $rekap = Rekapitulasi::query()->createOrFirst(
                ['kategori_rekap' => $kategori->value, 'periode_bulan' => $periode->bulan, 'periode_tahun' => $periode->tahun],
                ['tanggal_dibuat' => CarbonImmutable::today()->toDateString(), 'catatan' => $catatan],
            );
            $rekap = Rekapitulasi::query()->whereKey($rekap->getKey())->lockForUpdate()->firstOrFail();
            $rekap->update(['tanggal_dibuat' => CarbonImmutable::today()->toDateString(), 'catatan' => $catatan]);

            $sekarang = now();
            $baris = [];
            foreach ($item as $urutan => $i) {
                $baris[] = [
                    'id_rekapitulasi' => $rekap->id_rekapitulasi,
                    'kode_item' => $i->kode,
                    'nama_item' => $i->nama,
                    'nilai' => $i->nilai,
                    'urutan' => $urutan + 1,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
            RekapitulasiDetail::query()->upsert($baris, ['id_rekapitulasi', 'kode_item'], ['nama_item', 'nilai', 'urutan', 'updated_at']);
            RekapitulasiDetail::query()
                ->where('id_rekapitulasi', $rekap->id_rekapitulasi)
                ->whereNotIn('kode_item', array_column($baris, 'kode_item'))
                ->delete();

            return $rekap;
        });
    }
}
