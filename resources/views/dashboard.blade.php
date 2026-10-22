{{--
    ==========================================================
    DASHBOARD  ·  SKF-03 (Visualisasi Dashboard)
    Posyandu Mawar 1 - Capstone Project 2 (Frontend)
    Gambar 2.40 (Bulan Ini) & 2.41 (Arsip Bulan Sebelumnya)
    ==========================================================
    Data dari DashboardController@index:
      $tab ('bulan_ini'|'arsip') · $data (DashboardService::data)
      $bulanIni · $daftarTahun
    $data = ['periode' => Periode, 'kegiatan' => Collection,
             'filter_imt' => string, 'grafik' => [...]]
    ==========================================================
--}}

@php
    $periode = $data['periode'];
    $arsip   = $tab === 'arsip';
    $jeda    = max(2, (int) config('posyandu.presensi.polling_detik'));

    // Helper URL dengan filter yang tetap.
    $kueri = fn (array $tambahan = []) => array_filter(array_merge(
        $arsip ? ['tab' => 'arsip', 'bulan' => $periode->bulan, 'tahun' => $periode->tahun] : [],
        ['kategori' => $data['filter_imt'] !== 'semua' ? $data['filter_imt'] : null],
        $tambahan,
    ), fn ($v) => $v !== null);

    // Ringkasan angka dari grafik (data asli).
    $nilaiGrafik = fn (string $kunci) => collect($data['grafik'][$kunci]['batang'] ?? [])->pluck('nilai', 'kunci');
    $imt = $nilaiGrafik('imt');
    $ptm = $nilaiGrafik('ptm');
    $td  = $nilaiGrafik('td');
    $tbc = $nilaiGrafik('tbc');

    $totalDiperiksa = $imt->sum();
    $giziNormal     = (int) ($imt['normal'] ?? 0);
    $perluRujukan   = (int) ($tbc['bergejala'] ?? 0) + (int) ($td['risiko'] ?? 0)
                    + (int) (collect($ptm)->except('normal')->sum());
    $bergejalaTbc   = (int) ($tbc['bergejala'] ?? 0);

    $kartu = [
        ['label' => 'Warga Diperiksa', 'nilai' => $totalDiperiksa, 'suffix' => 'orang',
         'warna' => 'mawar',   'keterangan' => 'Total warga yang sudah diperiksa',
         'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM12 9.75h.008v.008H12V9.75z'],
        ['label' => 'Perlu Rujukan',   'nilai' => $perluRujukan, 'suffix' => 'orang',
         'warna' => 'rose',    'keterangan' => 'PTM, TD tinggi, atau gejala TBC',
         'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        ['label' => 'Status Gizi Normal', 'nilai' => $giziNormal, 'suffix' => 'orang',
         'warna' => 'emerald', 'keterangan' => 'IMT dalam rentang normal',
         'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Bergejala TBC',   'nilai' => $bergejalaTbc, 'suffix' => 'orang',
         'warna' => 'amber',   'keterangan' => 'Perlu dirujuk ke Puskesmas',
         'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0l1.518-1.14a1.587 1.587 0 012.06 0l.217.217c.52.521.372.52 1.893 0l.217-.217'],
    ];

    $urutanGrafik = ['imt', 'ptm', 'td', 'tbc'];
@endphp


<x-layouts.app title="Dashboard" active="dashboard">

    {{-- ═══════════ HEADER: TAB + EKSPOR ═══════════ --}}
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

        {{-- Tab --}}
        <div class="flex w-fit items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a href="{{ route('dashboard') }}"
               @class([
                   'rounded-lg px-4 py-2 text-[13px] font-semibold transition',
                   'bg-mawar-800 text-white shadow-sm' => ! $arsip,
                   'text-slate-600 hover:bg-slate-50'      => $arsip,
               ])>
                Bulan Ini ({{ $bulanIni->label() }})
            </a>

            <a href="{{ route('dashboard', ['tab' => 'arsip']) }}"
               @class([
                   'rounded-lg px-4 py-2 text-[13px] font-semibold transition',
                   'bg-mawar-800 text-white shadow-sm' => $arsip,
                   'text-slate-600 hover:bg-slate-50'      => ! $arsip,
               ])>
                Arsip Bulan Sebelumnya
            </a>
        </div>

        {{-- Ekspor Excel --}}
        <a href="{{ route('dashboard.ekspor', $kueri()) }}" class="btn btn-secondary py-2 text-[13px]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Ekspor ke Excel (.xlsx)
        </a>
    </div>


    {{-- ═══════════ FILTER (TAB ARSIP) ═══════════ --}}
    @if ($arsip)
        <form method="GET" action="{{ route('dashboard') }}"
              class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm
                     sm:flex-row sm:items-center">
            <input type="hidden" name="tab" value="arsip">
            @if ($data['filter_imt'] !== 'semua')
                <input type="hidden" name="kategori" value="{{ $data['filter_imt'] }}">
            @endif

            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pilih Periode</span>

            <select name="bulan" aria-label="Bulan" onchange="this.form.submit()"
                    class="input w-36 py-2 text-[13px]">
                @for ($b = 1; $b <= 12; $b++)
                    <option value="{{ $b }}" @selected($b === $periode->bulan)>
                        {{ \Carbon\CarbonImmutable::create(2000, $b, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>

            <select name="tahun" aria-label="Tahun" onchange="this.form.submit()"
                    class="input w-28 py-2 text-[13px]">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}" @selected($t === $periode->tahun)>{{ $t }}</option>
                @endforeach
            </select>

            <p class="text-[12px] text-slate-500">
                Menampilkan data: <strong class="font-semibold text-slate-800">{{ $periode->label() }}</strong>
            </p>
        </form>
    @else
        {{-- ═══════════ INFO PERIODE + POLLING ═══════════ --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200
                    bg-white px-4 py-3.5 shadow-sm">
            <div class="min-w-0 text-[13px] text-slate-600">
                Periode: <strong class="font-semibold text-slate-900">{{ $periode->label() }}</strong>
                <span class="mx-1.5 text-slate-300">·</span>
                @if ($data['kegiatan']->isEmpty())
                    <span class="text-amber-700">belum ada kegiatan Posyandu bulan ini</span>
                @else
                    {{ $data['kegiatan']->pluck('nama_kegiatan')->implode(', ') }}
                @endif
            </div>

            {{-- HOOK #indikator-langsung --}}
            <p id="indikator-langsung" class="flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-emerald-600">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                </span>
                Diperbarui otomatis setiap {{ $jeda }} detik
            </p>
        </div>
    @endif


    {{-- ═══════════ KARTU STATISTIK ═══════════ --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $i => $k)
            <div class="animate-fade-up" style="animation-delay: {{ $i * 70 }}ms">
                <x-ui.stat-card
                    :label="$k['label']"
                    :nilai="$k['nilai']"
                    :suffix="$k['suffix']"
                    :keterangan="$k['keterangan']"
                    :icon="$k['icon']"
                    :warna="$k['warna']" />
            </div>
        @endforeach
    </div>


    {{-- ═══════════ INFO STRIP ═══════════ --}}
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-mawar-100 bg-mawar-50/70 px-4 py-3.5">
        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
            </svg>
        </span>
        <p class="text-xs leading-relaxed text-mawar-800/85">
            @if ($arsip)
                Cara hitung sama dengan tab Bulan Ini (jumlah warga per kategori hasil ploting).
                Data arsip hanya ditampilkan dan tidak memengaruhi grafik bulan ini.
                <strong>Ekspor Excel</strong> mengunduh rekap periode yang dipilih.
            @else
                Cara hitung grafik: sistem menghitung <strong>jumlah (count) warga per kategori</strong> hasil
                ploting pada bulan berjalan, bukan rata-rata nilai. Sumber: ploting di form pemeriksaan
                (yang sudah Submit). Grafik bulan-bulan sebelumnya ada di tab
                <strong>Arsip Bulan Sebelumnya</strong>.
            @endif
        </p>
    </div>


    {{-- ═══════════ PERINGATAN KOSONG ═══════════ --}}
    @if ($data['kegiatan']->isEmpty())
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-amber-900">
                Tidak ada kegiatan Posyandu pada {{ $periode->label() }}, sehingga semua grafik bernilai 0.
            </p>
        </div>
    @endif


    {{-- ═══════════ 4 GRAFIK ═══════════ --}}
    <div id="grafik-dashboard"
         @unless ($arsip) data-url="{{ route('dashboard.data', $kueri()) }}"
         @unless ($arsip) data-jeda="{{ $jeda * 1000 }}" @endunless
         data-periode="{{ $periode->kode() }}"
         class="mt-6 grid gap-5 xl:grid-cols-2">

        @foreach ($urutanGrafik as $kunci)
            @if (isset($data['grafik'][$kunci]))
                @php $isi = $data['grafik'][$kunci]; @endphp

                <x-ui.chart-card
                    :kunci="$kunci"
                    :judul="$isi['judul']"
                    :subjudul="$isi['keterangan']"
                    :bars="array_map(fn ($b) => ['kunci' => $b['kunci'], 'label' => $b['label'], 'value' => $b['nilai']], $isi['batang'])">

                    @if ($kunci === 'imt')
                        <x-slot:footer>
                            <form method="GET" action="{{ route('dashboard') }}">
                                @if ($arsip)
                                    <input type="hidden" name="tab" value="arsip">
                                    <input type="hidden" name="bulan" value="{{ $periode->bulan }}">
                                    <input type="hidden" name="tahun" value="{{ $periode->tahun }}">
                                @endif

                                <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()"
                                        class="input w-32 shrink-0 border-0 py-1.5 text-xs ring-1 ring-mawar-200 focus:ring-mawar-500">
                                    @foreach (\App\Services\DashboardService::FILTER_IMT as $nilai => $label)
                                        <option value="{{ $nilai }}" @selected($data['filter_imt'] === $nilai)>
                                            {{ $nilai === 'semua' ? 'Semua Kategori' : $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </x-slot:footer>
                    @endif
                </x-ui.chart-card>
            @endif
        @endforeach
    </div>

    {{-- ═══════════ JS LIVE-UPDATE ═══════════ --}}
    <script src="{{ asset('js/dashboard.js') }}" defer></script>

</x-layouts.app>