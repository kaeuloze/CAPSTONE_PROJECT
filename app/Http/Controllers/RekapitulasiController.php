<?php

namespace App\Http\Controllers;

use App\Enums\KategoriRekap;
use App\Services\Rekapitulasi\EksporRekapExcel;
use App\Services\Rekapitulasi\Periode;
use App\Services\Rekapitulasi\RekapitulasiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu Rekapitulasi (SKF-12): halaman pilihan kategori (Figma 306:33, Gambar 2.69), tabel rekap per
 * kategori dengan filter Bulan/Tahun (Figma 306:103, 316:55, 329:79; Gambar 2.70–2.72), dan Ekspor Excel.
 * Parameter {kategori} = remaja / ibu_hamil_nifas / dewasa_lansia.
 */
class RekapitulasiController extends Controller
{
    public function __construct(
        private readonly RekapitulasiService $layanan,
        private readonly EksporRekapExcel $ekspor,
    ) {
    }

    public function index(): View
    {
        return view('rekapitulasi.index', [
            'daftarKategori' => KategoriRekap::cases(),
            'periodeBawaan' => $this->layanan->periodeBawaan(),
        ]);
    }

    public function show(Request $request, KategoriRekap $kategori): View
    {
        $periode = $this->periode($request);

        return view('rekapitulasi.show', [
            'rekap' => $this->layanan->susun($kategori, $periode),
            'judulResmi' => EksporRekapExcel::judul($kategori),
            'daftarTahun' => $this->layanan->daftarTahun(),
        ]);
    }

    public function ekspor(Request $request, KategoriRekap $kategori): StreamedResponse
    {
        $periode = $this->periode($request);
        $buku = $this->ekspor->buat($this->layanan->susun($kategori, $periode));

        return response()->streamDownload(
            fn () => $this->ekspor->tulis($buku),
            EksporRekapExcel::namaFile($kategori, $periode),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store'],
        );
    }

    /** Bulan/Tahun dari filter; tanpa filter = bulan kegiatan terakhir. Bulan setelah bulan ini ditolak. */
    private function periode(Request $request): Periode
    {
        $tahunIni = (int) CarbonImmutable::today()->format('Y');
        $data = $request->validate([
            'bulan' => ['nullable', 'required_with:tahun', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'required_with:bulan', 'integer', 'between:2000,'.$tahunIni],
        ], [
            'required_with' => 'Pilih bulan dan tahun.',
            'between' => 'Periode tidak valid.',
            'integer' => 'Periode tidak valid.',
        ]);

        if (! isset($data['bulan'])) {
            return $this->layanan->periodeBawaan();
        }

        $periode = new Periode((int) $data['bulan'], (int) $data['tahun']);
        if ($periode->setelahBulanIni()) {
            throw ValidationException::withMessages(['bulan' => 'Periode belum berjalan. Pilih bulan ini atau bulan sebelumnya.']);
        }

        return $periode;
    }
}
