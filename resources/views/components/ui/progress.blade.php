{{--
    Progress bar horizontal. Contoh:
    <x-ui.progress :nilai="31" :total="47" warna="mawar" />
--}}
@props([
    'nilai' => 0,
    'total' => 1,
    'warna' => 'mawar',   // mawar | emerald | amber | rose
    'tinggi' => 'h-2',
])

@php
    $persen = $total > 0 ? round(($nilai / $total) * 100) : 0;

    $warnaMap = [
        'mawar'   => 'bg-gradient-to-r from-mawar-700 to-mawar-400',
        'emerald' => 'bg-gradient-to-r from-emerald-600 to-emerald-400',
        'amber'   => 'bg-gradient-to-r from-amber-600 to-amber-400',
        'rose'    => 'bg-gradient-to-r from-rose-600 to-rose-400',
    ];
    $bar = $warnaMap[$warna] ?? $warnaMap['mawar'];
@endphp

<div class="w-full {{ $tinggi }} overflow-hidden rounded-full bg-slate-100">
    <div class="h-full rounded-full bg-{{ $bar }} transition-all duration-500"
         style="width: {{ $persen }}%"></div>
</div>