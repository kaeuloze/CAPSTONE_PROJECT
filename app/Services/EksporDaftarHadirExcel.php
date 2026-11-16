<?php

namespace App\Services;

use App\Models\Kegiatan;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Daftar hadir satu kegiatan dalam Excel (.xlsx) agar kader tidak lagi membuat absen manual
 * (permintaan kader 31 Okt 2026; hasil uji coba bersama kader 8 Nov 2026): hanya warga Hadir,
 * kolom No, Nama, NIK (4 digit terakhir), Kategori, dan jumlah hadir di bawah tabel.
 */
class EksporDaftarHadirExcel
{
    public const KOLOM = ['No', 'Nama', 'NIK (4 digit terakhir)', 'Kategori'];

    public static function judul(): string
    {
        return 'DAFTAR HADIR '.mb_strtoupper((string) config('posyandu.profil.nama'));
    }

    public static function namaFile(Kegiatan $kegiatan): string
    {
        return 'daftar-hadir-'.$kegiatan->tanggal_kegiatan->format('Y-m-d').'-'.$kegiatan->id_kegiatan.'.xlsx';
    }

    /**
     * Baris kop (label => isi), dipakai juga oleh halaman cetak.
     *
     * @return array<string, string>
     */
    public static function kop(Kegiatan $kegiatan): array
    {
        return [
            'Kegiatan' => $kegiatan->nama_kegiatan,
            'Tanggal' => $kegiatan->tanggalLengkap(),
            'Wilayah' => (string) config('posyandu.profil.wilayah') ?: '-',
        ];
    }

    /**
     * Total di bawah tabel (label => jumlah), dipakai juga oleh halaman cetak.
     *
     * @param  Collection<int, array<string, string>>  $daftar
     * @return array<string, int>
     */
    public static function total(Collection $daftar): array
    {
        return ['Total hadir' => $daftar->count()];
    }

    /** @param Collection<int, array{nama: string, nik4: string, kategori: string}> $daftar */
    public function buat(Kegiatan $kegiatan, Collection $daftar): Spreadsheet
    {
        $buku = new Spreadsheet;
        $buku->getProperties()->setTitle(self::judul())->setCreator((string) config('posyandu.profil.nama'));
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle('Daftar Hadir');

        $lembar->setCellValue('A1', self::judul());
        $lembar->mergeCells('A1:D1');
        $lembar->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $lembar->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $baris = 2;
        foreach (self::kop($kegiatan) as $label => $isi) {
            $baris++;
            $lembar->setCellValue("A{$baris}", $label);
            $lembar->mergeCells("A{$baris}:B{$baris}");
            // Teks dipaksa string agar isian tidak pernah dibaca sebagai rumus Excel.
            $lembar->setCellValueExplicit("C{$baris}", ': '.$isi, DataType::TYPE_STRING);
        }

        $kepala = $baris + 2;
        $lembar->fromArray(self::KOLOM, null, "A{$kepala}");
        $lembar->getStyle("A{$kepala}:D{$kepala}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $baris = $kepala;
        foreach ($daftar as $nomor => $w) {
            $baris++;
            $lembar->setCellValue("A{$baris}", $nomor + 1);
            $lembar->setCellValueExplicit("B{$baris}", $w['nama'], DataType::TYPE_STRING);
            $lembar->setCellValueExplicit("C{$baris}", $w['nik4'], DataType::TYPE_STRING); // teks agar angka 0 di depan tetap
            $lembar->setCellValueExplicit("D{$baris}", $w['kategori'], DataType::TYPE_STRING);
        }
        if ($daftar->isEmpty()) {
            $baris++;
            $lembar->setCellValue("A{$baris}", 'Belum ada warga yang hadir.');
            $lembar->mergeCells("A{$baris}:D{$baris}");
        }

        $lembar->getStyle("A{$kepala}:D{$baris}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A', 'C', 'D'] as $kolom) {
            $lembar->getStyle($kolom.($kepala + 1).":{$kolom}{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Jumlah warga hadir di bawah tabel.
        $baris++;
        foreach (self::total($daftar) as $label => $jumlah) {
            $baris++;
            $lembar->setCellValue("C{$baris}", $label);
            $lembar->setCellValue("D{$baris}", $jumlah);
            $lembar->getStyle("C{$baris}:D{$baris}")->getFont()->setBold(true);
            $lembar->getStyle("D{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $lembar->setCellValue('A'.($baris + 2), 'Diunduh pada '.now()->translatedFormat('j F Y H:i').' WIB.');

        foreach (['A' => 6, 'B' => 38, 'C' => 22, 'D' => 18] as $kolom => $lebar) {
            $lembar->getColumnDimension($kolom)->setWidth($lebar);
        }
        $lembar->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $lembar->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($kepala, $kepala); // kepala tabel di tiap halaman

        return $buku;
    }

    /** Menulis file ke output (dipakai response()->streamDownload). */
    public function tulis(Spreadsheet $buku): void
    {
        (new Xlsx($buku))->save('php://output');
        $buku->disconnectWorksheets();
    }
}
