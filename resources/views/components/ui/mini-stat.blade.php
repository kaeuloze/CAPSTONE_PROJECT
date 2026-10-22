{{--
    Angka kecil + label, untuk ringkasan di dalam kartu.
    <x-ui.mini-stat label="Total Warga" :nilai="47" warna="mawar" />
--}}
@props([
    'label' => '',
    'nilai' => 0,
    'suffix'=> '',
    'warna' => 'mawar',   // mawar | emerald | amber | rose | slate
])

@php
    $warnaMap = [
        'mawar'   => 'text-mawar-800',
        'emerald' => 'text-emerald-700',
        'amber'   => 'text-amber-700',
        'rose'    => 'text-rose-700',
        'slate'   => 'text-slate-600',
    ];
@endphp

<div class="text-center">
    <p class="text-2xl font-extrabold tabular-nums leading-none tracking-tight {{ $warnaMap[$warna] ?? $warnaMap['mawar'] }}">
        {{ $nilai }}<span class="text-sm font-semibold text-slate-400">{{ $suffix }}</span>
    </p>
    <p class="mt-1.5 text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
</div>