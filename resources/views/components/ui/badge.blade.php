@props(['warna' => 'slate'])

@php
    $warnaMap = [
        'emerald' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
        'sky'     => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
        'amber'   => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'violet'  => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200',
        'rose'    => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
        'slate'   => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . ($warnaMap[$warna] ?? $warnaMap['slate'])]) }}>
    {{ $slot }}
</span>