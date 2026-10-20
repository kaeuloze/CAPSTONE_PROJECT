<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Ekspor Excel Dashboard: angka setiap grafik untuk periode yang tampil (disetujui 21 Okt 2026). */
class EksporDashboardExcel
{
    public static function namaFile(array $data): string
    {
        return 'dashboard-'.$data['periode']->kode().'.xlsx';
    }

    /** @param array $data hasil DashboardService::data() */
    public function buat(array $data): Spreadsheet
    {
        $profil = config('posyandu.profil');
        $buku = new Spreadsheet;
        $buku->getProperties()->setTitle('Dashboard '.$profil['nama'])->setCreator((string) $profil['nama']);
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle('Dashboard '.$data['periode']->kode());

        $lembar->setCellValue('A1', 'DASHBOARD '.mb_strtoupper((string) $profil['nama']));
        $lembar->mergeCells('A1:B1');
        $lembar->getStyle('A1')->getFont()->setBold(true);
        $lembar->setCellValueExplicit('A2', 'Periode: '.$data['periode']->label(), DataType::TYPE_STRING);
        $lembar->setCellValueExplicit('A3', $data['kegiatan']->isEmpty()
            ? 'Tidak ada kegiatan posyandu pada periode ini.'
            : 'Kegiatan: '.$data['kegiatan']->map(fn ($k) => $k->nama_kegiatan.' ('.$k->tanggal_kegiatan->translatedFormat('j F Y').')')->implode(', '),
            DataType::TYPE_STRING);
        $lembar->setCellValueExplicit('A4', 'Cara hitung: jumlah warga per kategori hasil ploting (pemeriksaan Submit, tiap warga sekali).', DataType::TYPE_STRING);

        $baris = 6;
        foreach ($data['grafik'] as $kunci => $grafik) {
            $judul = $grafik['judul'];
            if ($kunci === 'imt') {
                $judul .= ' — '.DashboardService::FILTER_IMT[$data['filter_imt']];
            }
            $lembar->setCellValueExplicit("A{$baris}", $judul, DataType::TYPE_STRING);
            $lembar->setCellValue("B{$baris}", 'Jumlah Warga');
            $lembar->getStyle("A{$baris}:B{$baris}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ]);
            $awal = $baris;
            foreach ($grafik['batang'] as $batang) {
                $baris++;
                $lembar->setCellValueExplicit("A{$baris}", $batang['label'], DataType::TYPE_STRING);
                $lembar->setCellValue("B{$baris}", $batang['nilai']);
            }
            $lembar->getStyle("A{$awal}:B{$baris}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $lembar->getStyle("B{$awal}:B{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris += 2;
        }

        $lembar->setCellValue("A{$baris}", 'Diunduh pada '.now()->translatedFormat('j F Y H:i').' WIB.');
        $lembar->getColumnDimension('A')->setWidth(60);
        $lembar->getColumnDimension('B')->setWidth(16);

        return $buku;
    }

    public function tulis(Spreadsheet $buku): void
    {
        (new Xlsx($buku))->save('php://output');
        $buku->disconnectWorksheets();
    }
}
