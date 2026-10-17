@props([
    'label'      => '',
    'nilai'      => 0,
    'suffix'     => '',
    'keterangan' => '',
    'warna'      => 'mawar',   // mawar | emerald | amber | rose | sky
])

@php
    $warnaMap = [
        'mawar'   => ['text' => 'text-mawar-800',   'bar' => 'from-mawar-700 to-mawar-400',
                'dot' => 'bg-mawar-600'],
        'emerald' => ['text' => 'text-emerald-700', 'bar' => 'from-emerald-700 to-emerald-400',
                'dot' => 'bg-emerald-600'],
        'amber'   => ['text' => 'text-amber-700',   'bar' => 'from-amber-600 to-amber-400',
                'dot' => 'bg-amber-600'],
        'rose'    => ['text' => 'text-rose-700',    'bar' => 'from-rose-600 to-rose-400',
                'dot' => 'bg-rose-600'],
        'sky'     => ['text' => 'text-sky-700',     'bar' => 'from-sky-600 to-sky-400',
                'dot' => 'bg-sky-600'],
    ];
    $w = $warnaMap[$warna] ?? $warnaMap['mawar'];
@endphp

<article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5
 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-mawar-200
                hover:shadow-xl hover:shadow-mawar-900/5">

    {{-- Aksen garis atas --}}
    <span class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-gradient-to-r bg-{{ $w['bar'] }}
                 transition-transform duration-500 group-hover:scale-x-100"></span>

    {{-- Label --}}
    <p class="text-[13px] font-bold uppercase tracking-wide text-slate-700">
        {{ $label }}
    </p>

    {{-- Angka --}}
    <div class="mt-3 flex items-baseline gap-1.5">
        <span class="text-4xl font-extrabold tabular-nums leading-none tracking-tight {{ $w['text'] }}">
            {{ $nilai }}
        </span>
        @if($suffix)
            <span class="text-sm font-medium text-slate-500">{{ $suffix }}</span>
        @endif
    </div>

    {{-- Keterangan --}}
    @if($keterangan)
        <p class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3 text-[12px] font-medium text-slate-600">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $w['dot'] }}"></span>
            {{ $keterangan }}
        </p>
    @endif
</article>