{{-- Halaman siap cetak daftar hadir (permintaan kader 31 Okt 2026; koreksi tim 4 Nov 2026: semua warga
     Hadir & Tidak Hadir + total di bawah, tanpa jam hadir & tanda tangan). Halaman mandiri tanpa menu,
     gaya ditulis di sini agar hasil cetak rapi tanpa bergantung pada build Tailwind. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Hadir · {{ $kegiatan->nama_kegiatan }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font: 13px/1.45 Arial, Helvetica, sans-serif; }
        .aksi { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; padding: 16px; }
        .aksi a, .aksi button { border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a;
            padding: 8px 14px; font: 600 13px Arial, Helvetica, sans-serif; text-decoration: none; cursor: pointer; }
        .aksi button { background: #0e7490; border-color: #0e7490; color: #fff; }
        .lembar { width: 100%; max-width: 210mm; margin: 0 auto 24px; background: #fff; padding: 14mm 12mm; }
        h1 { margin: 0 0 12px; text-align: center; font-size: 16px; letter-spacing: .02em; }
        .kop { margin: 0 0 14px; border-collapse: collapse; }
        .kop td { padding: 1px 8px 1px 0; vertical-align: top; }
        .tabel { width: 100%; border-collapse: collapse; }
        .tabel th, .tabel td { border: 1px solid #334155; padding: 6px 8px; vertical-align: middle; }
        .tabel th { background: #e5e7eb; text-align: center; }
        .tengah { text-align: center; }
        .nik { font-family: "Courier New", monospace; white-space: nowrap; }
        .total { margin: 12px 0 0 auto; border-collapse: collapse; font-weight: 700; }
        .total td { padding: 2px 0 2px 12px; }
        .kosong { text-align: center; color: #64748b; }
        .catatan { margin-top: 10px; color: #64748b; font-size: 11px; }
        @page { size: A4 portrait; margin: 12mm; }
        @media print {
            body { background: #fff; }
            .aksi { display: none; }
            .lembar { max-width: none; margin: 0; padding: 0; }
            .tabel thead { display: table-header-group; } /* kepala tabel diulang di tiap halaman */
            .tabel tr { page-break-inside: avoid; }
        }
        @media (max-width: 640px) {
            .lembar { padding: 16px; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="aksi">
        <a href="{{ route('presensi.index', $kegiatan) }}">&larr; Kembali ke Presensi</a>
        <button type="button" onclick="window.print()">Cetak Lagi</button>
    </div>

    <main class="lembar">
        <h1>{{ $judul }}</h1>

        <table class="kop">
            @foreach ($kop as $label => $isi)
                <tr><td>{{ $label }}</td><td>: {{ $isi }}</td></tr>
            @endforeach
        </table>

        <table class="tabel">
            <thead>
                <tr>
                    @foreach ($kolom as $judulKolom)
                        <th>{{ $judulKolom }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $w)
                    <tr>
                        <td class="tengah">{{ $loop->iteration }}</td>
                        <td>{{ $w['nama'] }}</td>
                        <td class="nik">{{ $w['nik'] }}</td>
                        <td class="tengah">{{ $w['kategori'] }}</td>
                        <td class="tengah">{{ \App\Services\EksporDaftarHadirExcel::statusKehadiran($w['hadir']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($kolom) }}" class="kosong">Belum ada warga yang tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Total di bawah tabel (permintaan tim 4 Nov 2026) --}}
        <table class="total">
            @foreach ($total as $label => $jumlah)
                <tr><td>{{ $label }}</td><td>: {{ $jumlah }} warga</td></tr>
            @endforeach
        </table>

        <p class="catatan">Dicetak dari Sistem Informasi {{ config('posyandu.profil.nama') }} pada {{ now()->locale('id')->translatedFormat('j F Y H:i') }} WIB.</p>
    </main>

    {{-- Langsung membuka dialog cetak begitu halaman siap (permintaan pengguna 2 Nov 2026). --}}
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
