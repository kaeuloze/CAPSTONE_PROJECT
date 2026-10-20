<?php

namespace App\Services\Rekapitulasi;

use App\Enums\KategoriRekap;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Membuat file Excel (.xlsx) satu bulan, isinya sama dengan tabel di layar (disetujui 21 Okt 2026),
 * dengan kop mengikuti formulir kertas Kemenkes (judul, Posyandu, Dusun/RT/RW, Desa, Kecamatan).
 */
class EksporRekapExcel
{
    /** Judul resmi formulir kertas per kategori. */
    public static function judul(KategoriRekap $kategori): string
    {
        return match ($kategori) {
            KategoriRekap::Remaja => 'REKAPITULASI HASIL PEMERIKSAAN ANAK USIA SEKOLAH DAN REMAJA',
            KategoriRekap::IbuHamilNifas => 'REKAPITULASI HASIL PEMERIKSAAN IBU HAMIL/NIFAS/MENYUSUI',
            KategoriRekap::DewasaLansia => 'REKAPITULASI HASIL PEMERIKSAAN USIA DEWASA DAN USIA LANJUT',
        };
    }

    public static function namaFile(KategoriRekap $kategori, Periode $periode): string
    {
        return 'rekap-'.str_replace('_', '-', $kategori->value).'-'.$periode->kode().'.xlsx';
    }

    /** @param array{kategori: KategoriRekap, periode: Periode, item: list<ItemRekap>} $rekap */
    public function buat(array $rekap): Spreadsheet
    {
        $profil = config('posyandu.profil');
        $buku = new Spreadsheet;
        $buku->getProperties()->setTitle(self::judul($rekap['kategori']))->setCreator((string) $profil['nama']);
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle('Rekap '.$rekap['periode']->kode());

        $lembar->setCellValue('A1', self::judul($rekap['kategori']));
        $lembar->setCellValue('A2', 'POSYANDU '.mb_strtoupper((string) $profil['nama_tanpa_awalan']));
        foreach (['A1:C1', 'A2:C2'] as $rentang) {
            $lembar->mergeCells($rentang);
            $lembar->getStyle($rentang)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $lembar->getStyle('A1:A2')->getFont()->setBold(true);

        $kop = [
            ['Dusun/RT/RW', $profil['dusun_rt_rw']],
            ['Desa/Kelurahan/Nagari', $profil['desa']],
            ['Kecamatan', $profil['kecamatan']],
            ['Periode', $rekap['periode']->label()],
        ];
        foreach ($kop as $i => [$label, $isi]) {
            $baris = 4 + $i;
            $lembar->setCellValue("A{$baris}", $label);
            $lembar->setCellValueExplicit("B{$baris}", ': '.$isi, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $kepala = 9;
        $lembar->fromArray(['No', 'Rekapitulasi', 'Bulan dan Tahun: '.$rekap['periode']->label()], null, "A{$kepala}");
        $lembar->getStyle("A{$kepala}:C{$kepala}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = $kepala;
        foreach ($rekap['item'] as $nomor => $item) {
            $baris++;
            $lembar->setCellValue("A{$baris}", $nomor + 1);
            // Teks dipaksa string agar label tidak pernah dibaca sebagai rumus Excel.
            $lembar->setCellValueExplicit("B{$baris}", $item->nama, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $lembar->setCellValue("C{$baris}", $item->nilai);
        }
        $lembar->getStyle("A{$kepala}:C{$baris}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $lembar->getStyle("A".($kepala + 1).":A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $lembar->getStyle("C".($kepala + 1).":C{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $catatan = $baris + 2;
        $lembar->setCellValue("A{$catatan}", 'Catatan:');
        $lembar->setCellValue('A'.($catatan + 1), 'Angka terisi otomatis dari hasil pemeriksaan yang tersimpan pada periode terpilih.');
        $lembar->setCellValue('A'.($catatan + 2), 'Format ini dapat menggunakan buku bantu Posyandu.');
        $lembar->setCellValue('A'.($catatan + 3), 'Diunduh pada '.now()->translatedFormat('j F Y H:i').' WIB.');

        $lembar->getColumnDimension('A')->setWidth(6);
        $lembar->getColumnDimension('B')->setWidth(58);
        $lembar->getColumnDimension('C')->setWidth(24);
        $lembar->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        return $buku;
    }

    /** Menulis file ke output (dipakai response()->streamDownload). */
    public function tulis(Spreadsheet $buku): void
    {
        (new Xlsx($buku))->save('php://output');
        $buku->disconnectWorksheets();
    }
}
