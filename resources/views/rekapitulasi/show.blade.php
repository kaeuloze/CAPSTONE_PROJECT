{{--
    Tabel Rekapitulasi per kategori (Figma 306:103 Remaja, 316:55 Dewasa & Lansia, 329:79 Ibu Hamil/Nifas;
    Gambar 2.70–2.72; Activity Diagram 2.33–2.35). Data: $rekap (kategori, periode, item, kegiatan, acuan), $judulResmi, $daftarTahun.
--}}
@extends('layouts.app')

@php
    $periode = $rekap['periode'];
    $kategori = $rekap['kategori'];
@endphp

@section('judul', 'Rekapitulasi — '.$kategori->label())
@section('subjudul', 'Rekap otomatis dari hasil pemeriksaan kategori '.$kategori->label().' per periode.')
@section('aksi-kepala')
    <div class="aksi">
        <a href="{{ route('rekapitulasi.ekspor', [$kategori, 'bulan' => $periode->bulan, 'tahun' => $periode->tahun]) }}" class="tombol abu">Ekspor ke Excel (.xlsx)</a>
        <a href="{{ route('rekapitulasi.index') }}" class="tombol">← Kembali</a>
    </div>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Filter Bulan / Tahun --}}
    <form method="GET" action="{{ route('rekapitulasi.show', $kategori) }}" class="saring" aria-label="Filter periode">
        <select name="bulan" aria-label="Bulan">
            @foreach (range(1, 12) as $b)
                <option value="{{ $b }}" @selected($b === $periode->bulan)>{{ \Carbon\CarbonImmutable::create(2000, $b, 1)->translatedFormat('F') }}</option>
            @endforeach
        </select>
        <select name="tahun" aria-label="Tahun">
            @foreach ($daftarTahun as $t)
                <option value="{{ $t }}" @selected($t === $periode->tahun)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit" class="tombol">Tampilkan</button>
    </form>

    <section class="kartu">
        <p class="ket" style="margin:0">Periode</p>
        <p style="margin:4px 0 0;font-weight:600">{{ $periode->label() }}</p>
        <p class="ket" style="margin:6px 0 0">
            @if ($rekap['kegiatan']->isEmpty())
                Tidak ada kegiatan posyandu pada periode ini, sehingga angka pemeriksaan bernilai 0.
            @else
                Kegiatan: {{ $rekap['kegiatan']->map(fn ($k) => $k->nama_kegiatan.' ('.$k->tanggal_kegiatan->translatedFormat('j F Y').')')->implode(', ') }}
            @endif
        </p>
    </section>

    <div class="bungkus-tabel">
        <table>
            <thead>
                <tr>
                    <th scope="col">Item Rekapitulasi</th>
                    <th scope="col" style="width:160px;text-align:center">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rekap['item'] as $item)
                    <tr data-kode="{{ $item->kode }}">
                        <td>{{ $item->nama }}</td>
                        <td style="text-align:center;font-weight:600">{{ $item->nilai }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="info">
        Angka terisi otomatis dari hasil pemeriksaan yang sudah di-Submit pada periode (Bulan/Tahun) terpilih; tiap warga dihitung sekali.
        Jumlah sasaran dihitung pada {{ $rekap['acuan']->translatedFormat('j F Y') }}.<br>
        Mengikuti format resmi "{{ \Illuminate\Support\Str::title(mb_strtolower($judulResmi)) }}" (Kemenkes). Catatan form: format ini dapat menggunakan buku bantu Posyandu.
    </div>
@endsection
