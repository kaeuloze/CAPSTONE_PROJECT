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
 * (permintaan kader, 31 Okt 2026). Kop sederhana; kolom No, Nama, NIK, Kategori, Jam Hadir,
 * Tanda Tangan (kosong, diisi di kertas).
 */
class EksporDaftarHadirExcel
{
    public const KOLOM = ['No', 'Nama', 'NIK', 'Kategori', 'Jam Hadir', 'Tanda Tangan'];

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
    public static function kop(Kegiatan $kegiatan, int $jumlahHadir): array
    {
        return [
            'Kegiatan' => $kegiatan->nama_kegiatan,
            'Tanggal' => $kegiatan->tanggalLengkap(),
            'Wilayah' => (string) config('posyandu.profil.wilayah') ?: '-',
            'Jumlah hadir' => $jumlahHadir.' warga',
        ];
    }

    /** @param Collection<int, array{nama: string, nik: string, kategori: string, jam: string}> $daftar */
    public function buat(Kegiatan $kegiatan, Collection $daftar): Spreadsheet
    {
        $buku = new Spreadsheet;
        $buku->getProperties()->setTitle(self::judul())->setCreator((string) config('posyandu.profil.nama'));
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle('Daftar Hadir');

        $lembar->setCellValue('A1', self::judul());
        $lembar->mergeCells('A1:F1');
        $lembar->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $lembar->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $baris = 2;
        foreach (self::kop($kegiatan, $daftar->count()) as $label => $isi) {
            $baris++;
            $lembar->setCellValue("A{$baris}", $label);
            $lembar->mergeCells("A{$baris}:B{$baris}");
            // Teks dipaksa string agar isian tidak pernah dibaca sebagai rumus Excel.
            $lembar->setCellValueExplicit("C{$baris}", ': '.$isi, DataType::TYPE_STRING);
        }

        $kepala = $baris + 2;
        $lembar->fromArray(self::KOLOM, null, "A{$kepala}");
        $lembar->getStyle("A{$kepala}:F{$kepala}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $baris = $kepala;
        foreach ($daftar as $nomor => $w) {
            $baris++;
            $lembar->setCellValue("A{$baris}", $nomor + 1);
            $lembar->setCellValueExplicit("B{$baris}", $w['nama'], DataType::TYPE_STRING);
            $lembar->setCellValueExplicit("C{$baris}", $w['nik'], DataType::TYPE_STRING); // NIK tetap 16 digit, tidak jadi angka
            $lembar->setCellValueExplicit("D{$baris}", $w['kategori'], DataType::TYPE_STRING);
            $lembar->setCellValueExplicit("E{$baris}", $w['jam'], DataType::TYPE_STRING);
            $lembar->getRowDimension($baris)->setRowHeight(28); // ruang tanda tangan
        }
        if ($daftar->isEmpty()) {
            $baris++;
            $lembar->setCellValue("A{$baris}", 'Belum ada warga yang hadir.');
            $lembar->mergeCells("A{$baris}:F{$baris}");
        }

        $lembar->getStyle("A{$kepala}:F{$baris}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $lembar->getStyle('A'.($kepala + 1).":F{$baris}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['A', 'D', 'E'] as $kolom) {
            $lembar->getStyle($kolom.($kepala + 1).":{$kolom}{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $lembar->setCellValue('A'.($baris + 2), 'Diunduh pada '.now()->translatedFormat('j F Y H:i').' WIB.');

        foreach (['A' => 6, 'B' => 32, 'C' => 21, 'D' => 15, 'E' => 11, 'F' => 26] as $kolom => $lebar) {
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
