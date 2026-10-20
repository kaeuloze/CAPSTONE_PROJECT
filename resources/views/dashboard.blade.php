{{--
    Dashboard SEMENTARA (Figma 409:55 Bulan Ini, 409:185 Arsip Bulan Sebelumnya). Tampilan akhir oleh tim frontend.
    Data: $tab ('bulan_ini'|'arsip'), $data (DashboardService::data: periode, kegiatan, filter_imt, grafik[imt|ptm|td|tbc]),
    $bulanIni, $daftarTahun. Polling: route('dashboard.data') + public/js/dashboard.js (hanya tab Bulan Ini).
--}}
@extends('layouts.app')

@php
    $periode = $data['periode'];
    $arsip = $tab === 'arsip';
    $kueri = fn (array $tambahan = []) => array_filter(array_merge(
        $arsip ? ['tab' => 'arsip', 'bulan' => $periode->bulan, 'tahun' => $periode->tahun] : [],
        ['kategori' => $data['filter_imt'] !== 'semua' ? $data['filter_imt'] : null],
        $tambahan,
    ), fn ($v) => $v !== null);
@endphp

@section('judul', 'Dashboard')
@section('subjudul', 'Pusat informasi & rekap — grafik diperbarui otomatis saat data masuk · Masuk sebagai '.auth()->user()->nama_kader)
@section('aksi-kepala')
    <a href="{{ route('dashboard.ekspor', $kueri()) }}" class="tombol abu">Ekspor ke Excel (.xlsx)</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">{{ $errors->first() }}</div>
    @endif

    <nav class="tab" aria-label="Periode dashboard">
        <a href="{{ route('dashboard') }}" @class(['aktif' => ! $arsip]) @if (! $arsip) aria-current="page" @endif>Bulan Ini ({{ $bulanIni->label() }})</a>
        <a href="{{ route('dashboard', ['tab' => 'arsip']) }}" @class(['aktif' => $arsip]) @if ($arsip) aria-current="page" @endif>Arsip Bulan Sebelumnya</a>
    </nav>

    @if ($arsip)
        <form method="GET" action="{{ route('dashboard') }}" class="saring" aria-label="Pilih periode">
            <input type="hidden" name="tab" value="arsip">
            @if ($data['filter_imt'] !== 'semua')<input type="hidden" name="kategori" value="{{ $data['filter_imt'] }}">@endif
            <span class="ket">Pilih periode:</span>
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
            <span>Menampilkan data: <strong>{{ $periode->label() }}</strong></span>
        </form>
    @else
        <p class="ket">Periode: <strong>{{ $periode->label() }}</strong>
            · {{ $data['kegiatan']->isEmpty() ? 'belum ada kegiatan posyandu bulan ini' : 'data dari '.$data['kegiatan']->pluck('nama_kegiatan')->implode(', ') }}
            · <span id="indikator-langsung" class="langsung">Diperbarui otomatis setiap {{ max(2, (int) config('posyandu.presensi.polling_detik')) }} detik</span></p>
    @endif

    <div class="info">
        @if ($arsip)
            Pilih Bulan dan Tahun untuk melihat grafik periode sebelumnya. Cara hitung sama dengan tab Bulan Ini (jumlah warga per kategori hasil ploting). Data arsip hanya ditampilkan dan tidak memengaruhi grafik bulan ini; Ekspor Excel mengunduh rekap periode yang dipilih.
        @else
            Cara hitung grafik: sistem menghitung jumlah (count) warga per kategori hasil ploting pada bulan berjalan, bukan rata-rata nilai. Sumber: ploting di form pemeriksaan (yang sudah Submit). Grafik bulan-bulan sebelumnya ada di tab Arsip Bulan Sebelumnya; Ekspor Excel mengunduh rekap angka bulan ini.
        @endif
    </div>

    @if ($data['kegiatan']->isEmpty())
        <div class="pesan info-arsip">Tidak ada kegiatan posyandu pada {{ $periode->label() }}, sehingga semua grafik bernilai 0.</div>
    @endif

    <div class="kisi-grafik" id="grafik-dashboard"
         @unless ($arsip) data-url="{{ route('dashboard.data', $kueri()) }}" data-jeda="{{ max(2, (int) config('posyandu.presensi.polling_detik')) * 1000 }}" data-periode="{{ $periode->kode() }}" @endunless>
        @foreach ($data['grafik'] as $kunci => $grafik)
            @php $maks = max([1, ...array_column($grafik['batang'], 'nilai')]); @endphp
            <section class="kartu grafik" data-grafik="{{ $kunci }}">
                <div class="kepala-grafik">
                    <div>
                        <h2>{{ $grafik['judul'] }}</h2>
                        <p class="ket">{{ $grafik['keterangan'] }}</p>
                    </div>
                    @if ($kunci === 'imt')
                        <form method="GET" action="{{ route('dashboard') }}">
                            @if ($arsip)
                                <input type="hidden" name="tab" value="arsip"><input type="hidden" name="bulan" value="{{ $periode->bulan }}"><input type="hidden" name="tahun" value="{{ $periode->tahun }}">
                            @endif
                            <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()">
                                @foreach (\App\Services\DashboardService::FILTER_IMT as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected($data['filter_imt'] === $nilai)>{{ $nilai === 'semua' ? 'Filter Kategori: Semua' : $label }}</option>
                                @endforeach
                            </select>
                            <noscript><button type="submit" class="tombol kecil">Terapkan</button></noscript>
                        </form>
                    @endif
                </div>
                <div class="batang-grafik" role="img" aria-label="{{ $grafik['judul'] }}: {{ collect($grafik['batang'])->map(fn ($b) => $b['label'].' '.$b['nilai'])->implode(', ') }}">
                    @foreach ($grafik['batang'] as $batang)
                        <div class="batang" data-kunci="{{ $batang['kunci'] }}">
                            <span class="nilai-batang">{{ $batang['nilai'] }}</span>
                            <span class="isi-batang" style="height: {{ round($batang['nilai'] / $maks * 100) }}%"></span>
                            <span class="label-batang">{{ $batang['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection

@push('skrip')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
