@php
    $periode  = $rekap['periode'];
    $kategori = $rekap['kategori'];
    $total    = collect($rekap['item'])->sum('nilai');
@endphp


<x-layouts.app :title="'Rekapitulasi — ' . $kategori->label()" active="rekap">

    {{-- ═══════════ KEMBALI ═══════════ --}}
    <a href="{{ route('rekapitulasi.index') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Kembali ke pilihan kategori
    </a>


    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $kategori->label() }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                Rekap otomatis dari hasil pemeriksaan kategori <strong class="font-semibold">{{ $kategori->label() }}</strong> per periode.
            </p>
        </div>

        <a href="{{ route('rekapitulasi.ekspor', [$kategori, 'bulan' => $periode->bulan, 'tahun' => $periode->tahun]) }}"
           class="btn btn-secondary py-2 text-[13px]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Ekspor ke Excel (.xlsx)
        </a>
    </div>


    {{-- ═══════════ ERROR ═══════════ --}}
    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-rose-800">{{ $errors->first() }}</p>
        </div>
    @endif


    {{-- ═══════════ FILTER PERIODE ═══════════ --}}
    <form method="GET" action="{{ route('rekapitulasi.show', $kategori) }}"
          class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm
                 sm:flex-row sm:items-center">

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
            Menampilkan: <strong class="font-semibold text-slate-800">{{ $periode->label() }}</strong>
        </p>
    </form>


    {{-- ═══════════ RINGKASAN PERIODE ═══════════ --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-3">

        {{-- Periode --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Periode</p>
            <p class="mt-2 text-lg font-extrabold tracking-tight text-mawar-800">{{ $periode->label() }}</p>
        </div>

        {{-- Jumlah item terisi --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Terisi</p>
            <p class="mt-2 text-lg font-extrabold tabular-nums tracking-tight text-slate-800">{{ $total }}</p>
        </div>

        {{-- Acuan sasaran --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Jumlah Sasaran</p>
            <p class="mt-2 text-lg font-extrabold tracking-tight text-slate-800">
                {{ $rekap['acuan']->translatedFormat('j F Y') }}
            </p>
        </div>
    </div>


    {{-- ═══════════ INFO KEGIATAN ═══════════ --}}
    @if ($rekap['kegiatan']->isEmpty())
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-amber-900">
                Tidak ada kegiatan Posyandu pada periode ini, sehingga angka pemeriksaan bernilai 0.
            </p>
        </div>
    @else
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-slate-200/80 bg-white px-4 py-3.5">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
                </svg>
            </span>
            <p class="text-[13px] leading-relaxed text-slate-600">
                <span class="font-semibold text-slate-900">Kegiatan:</span>
                {{ $rekap['kegiatan']->map(fn ($k) => $k->nama_kegiatan . ' (' . $k->tanggal_kegiatan->translatedFormat('j F Y') . ')')->implode(', ') }}
            </p>
        </div>
    @endif


    {{-- ═══════════ TABEL REKAP ═══════════ --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="th w-16">No</th>
                        <th class="th">Item Rekapitulasi</th>
                        <th class="th w-40 text-center">Jumlah</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($rekap['item'] as $i => $item)
                        <tr data-kode="{{ $item->kode }}" class="transition hover:bg-mawar-50/40">
                            <td class="td text-slate-400">{{ $i + 1 }}</td>
                            <td class="td font-medium text-slate-800">{{ $item->nama }}</td>
                            <td class="td text-center">
                                <span class="inline-flex min-w-[2.5rem] items-center justify-center rounded-lg
                                             px-2.5 py-1 text-[13px] font-bold tabular-nums
                                             {{ (int) $item->nilai > 0
                                                ? 'bg-mawar-100 text-mawar-800'
                                                : 'bg-slate-100 text-slate-400' }}">
                                    {{ $item->nilai }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="td py-12 text-center text-slate-400">
                                Belum ada item rekapitulasi untuk kategori ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- ═══════════ INFO KEMENKES ═══════════ --}}
    <p class="mt-5 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
              text-[12px] leading-relaxed text-slate-500">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <span>
            Angka terisi otomatis dari hasil pemeriksaan yang sudah di-<strong>Submit</strong> pada periode
            (Bulan/Tahun) terpilih; tiap warga dihitung sekali. Jumlah sasaran dihitung pada
            <strong>{{ $rekap['acuan']->translatedFormat('j F Y') }}</strong>.
            Mengikuti format resmi
            <strong>"{{ \Illuminate\Support\Str::title(mb_strtolower($judulResmi)) }}"</strong> (Kemenkes).
            Catatan form: format ini dapat menggunakan buku bantu Posyandu.
        </span>
    </p>

</x-layouts.app>