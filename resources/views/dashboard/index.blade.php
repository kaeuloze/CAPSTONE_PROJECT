@php
    $ref = include resource_path('views/data/reference.php');

    $bulan = ['Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $tahun = [2026, 2025, 2024];

    $s = $ref['statistik'];

    $kartu = [
        [
            'label' => 'Warga Diperiksa', 'nilai' => $s['warga_diperiksa'], 'suffix' => 'orang',
            'warna' => 'mawar', 'keterangan' => 'Total pemeriksaan bulan ini',
            'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM12 9.75h.008v.008H12V9.75z',
        ],
        [
            'label' => 'Perlu Rujukan', 'nilai' => $s['perlu_rujukan'], 'suffix' => 'orang',
            'warna' => 'rose', 'keterangan' => 'Rujukan',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        [
            'label' => 'Status Gizi Normal', 'nilai' => $s['gizi_normal'], 'suffix' => 'orang',
            'warna' => 'emerald', 'keterangan' => 'IMT 18,5 – 24,9',
            'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Bergejala TBC', 'nilai' => $s['bergejala_tbc'], 'suffix' => 'orang',
            'warna' => 'amber', 'keterangan' => 'Gejala ≥ 2 → perlu rujukan',
            'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0l1.518-1.14a1.587 1.587 0 012.06 0l.217.217c.52.521.372.52 1.893 0l.217-.217',
        ],
    ];

    $grafik = [
        'imt' => [
            'judul'    => 'Distribusi Status Gizi (IMT)',
            'subjudul' => 'Jumlah warga per kategori berat badan / tinggi badan',
            'bars'     => [
                ['label' => 'Normal', 'value' => 31],
                ['label' => 'Kurus',       'value' => 8],
                ['label' => 'Rendah',      'value' => 5],
                ['label' => 'Gemuk',       'value' => 12],
            ],
        ],
        'ptm' => [
            'judul'    => 'Grafik Risiko PTM',
            'subjudul' => 'Jumlah warga dengan risiko penyakit tidak menular — Dewasa/Lansia',
            'bars'     => [
                ['label' => 'Normal',  'value' => 28],
                ['label' => 'DM',      'value' => 14],
                ['label' => 'Stroke',  'value' => 11],
                ['label' => 'Jantung', 'value' => 8],
                ['label' => 'Asma',    'value' => 7],
                ['label' => 'Ginjal',  'value' => 5],
                ['label' => 'Rabun',   'value' => 4],
            ],
        ],
        'td' => [
            'judul'    => 'Distribusi Tekanan Darah',
            'subjudul' => 'Jumlah warga per kategori tekanan darah',
            'bars'     => [
                ['label' => 'Normal',     'value' => 38],
                ['label' => 'Hipertensi', 'value' => 9],
            ],
        ],
        'tbc' => [
            'judul'    => 'Skrining TBC',
            'subjudul' => 'Jumlah warga dengan gejala TBC ≥ 1 = perlu rujukan',
            'bars'     => [
                ['label' => 'Normal',        'value' => 45],
                ['label' => 'Perlu Rujukan', 'value' => 2],
            ],
        ],
    ];
@endphp


<x-layouts.app title="Dashboard" active="dashboard">

    {{-- ═══════════ HEADER: TAB + FILTER + EKSPOR ═══════════ --}}
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

        {{-- Tab periode --}}
        <div class="flex w-fit items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a href="#"
               class="rounded-lg bg-mawar-800 px-4 py-2 text-[13px] font-semibold text-white shadow-sm">
                Bulan Ini
            </a>
            <a href="#"
               class="rounded-lg px-4 py-2 text-[13px] font-medium text-slate-600 transition hover:bg-slate-50">
                Arsip Bulan Sebelumnya
            </a>
        </div>

        {{-- Filter + Ekspor --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <select class="input w-36 py-2 text-[13px]">
                @foreach ($bulan as $b)
                    <option @selected($b === 'Oktober')>{{ $b }}</option>
                @endforeach
            </select>

            <select class="input w-24 py-2 text-[13px]">
                @foreach ($tahun as $t)
                    <option @selected($t === 2026)>{{ $t }}</option>
                @endforeach
            </select>

            <button type="button"
                    class="btn btn-secondary py-2 text-[13px]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M316.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Ekspor Excel
            </button>
        </div>
    </div>


    {{-- ═══════════ 4 KARTU STATISTIK ═══════════ --}}
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
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-mawar-100 bg-mawar-50/70 px-4 py-3">
        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
            </svg>
        </span>
        <p class="text-xs leading-relaxed text-mawar-800/85">
            Grafik di bawah dihitung dari <strong>jumlah warga per kategori</strong> hasil pemeriksaan
            pada periode terpilih — bukan nilai rata-rata.
        </p>
    </div>


    {{-- ═══════════ 4 GRAFIK ═══════════ --}}
    <div class="mt-6 grid gap-4 xl:grid-cols-2">

        <x-ui.chart-card
            :judul="$grafik['imt']['judul']"
            :subjudul="$grafik['imt']['subjudul']"
            :bars="$grafik['imt']['bars']">
            <x-slot:footer>
                <select class="input w-32 shrink-0 py-1.5 text-xs ring-1 ring-mawar-200 focus:ring-mawar-500">
                    <option>Semua Kategori</option>
                    <option>Remaja</option>
                    <option>Dewasa</option>
                    <option>Lansia</option>
                    <option>Ibu Hamil/Menyusui</option>
                </select>
            </x-slot:footer>
        </x-ui.chart-card>

        <x-ui.chart-card :judul="$grafik['ptm']['judul']" :subjudul="$grafik['ptm']['subjudul']" :bars="$grafik['ptm']['bars']" />
        <x-ui.chart-card :judul="$grafik['td']['judul']"  :subjudul="$grafik['td']['subjudul']"  :bars="$grafik['td']['bars']" />
        <x-ui.chart-card :judul="$grafik['tbc']['judul']" :subjudul="$grafik['tbc']['subjudul']" :bars="$grafik['tbc']['bars']" />

    </div>


</x-layouts.app>