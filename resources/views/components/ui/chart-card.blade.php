{{--
    Kartu grafik batang (CSS murni, tanpa library).
    Mendukung polling realtime: atribut data-grafik / data-kunci / .nilai-batang / .isi-batang
    dipakai public/js/dashboard.js untuk memperbarui angka tanpa reload.
--}}
@props([
    'judul'    => '',
    'subjudul' => '',
    'bars'     => [],   // [['kunci' => 'normal', 'label' => 'Normal', 'value' => 31], ...]
    'suffix'   => 'warga',
    'kunci'    => null,  // key grafik (imt/ptm/td/tbc) untuk polling
])

@php
    $max   = max(collect($bars)->max('value') ?: 1, 1);
    $total = collect($bars)->sum('value');
@endphp

<div @if($kunci) data-grafik="{{ $kunci }}" @endif
     class="group/card relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white
            shadow-sm transition duration-300
            hover:-translate-y-0.5 hover:border-mawar-200 hover:shadow-xl hover:shadow-mawar-900/5">

    {{-- Aksen garis atas --}}
    <span class="absolute inset-x-0 top-0 h-0.5 origin-left scale-x-0 bg-gradient-to-r bg-mawar-700 to-mawar-400
                 transition-transform duration-500 group-hover/card:scale-x-100"></span>

    {{-- ══════════ HEADER ══════════ --}}
    <div class="flex items-start justify-between gap-4 p-5 pb-0">
        <div class="min-w-0">
            <h3 class="flex items-center gap-2 text-[15px] font-semibold leading-tight text-slate-900">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-mawar-600"></span>
                {{ $judul }}
            </h3>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-400">{{ $subjudul }}</p>
        </div>

        {{ $footer ?? '' }}
    </div>

    {{-- ══════════ TOTAL ══════════ --}}
    <div class="mt-4 flex items-end gap-1.5 px-5">
        <span class="text-3xl font-extrabold tabular-nums leading-none tracking-tight text-mawar-800">
            {{ $total }}
        </span>
        <span class="pb-0.5 text-xs text-slate-400">{{ $suffix }}</span>
    </div>

    {{-- ══════════ GRAFIK ══════════ --}}
    <div class="px-5 pb-5">
        <div class="relative mt-5 flex h-44 items-end gap-3 border-b border-slate-200">

            {{-- Garis bantu --}}
            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                @for ($g = 0; $g < 4; $g++)
                    <span class="block w-full border-t border-dashed border-slate-200/80"></span>
                @endfor
            </div>

            @foreach ($bars as $i => $bar)
                @php $tinggi = max(6, round(($bar['value'] / $max) * 78)); @endphp

                <div class="group relative flex h-full flex-1 flex-col justify-end"
                     @if(! empty($bar['kunci'])) data-kunci="{{ $bar['kunci'] }}" @endif>

                    {{-- Nilai (HOOK .nilai-batang) --}}
                    <span class="nilai-batang mb-2 block text-center text-[11px] font-bold tabular-nums
                                 text-slate-500 transition group-hover:text-mawar-800">
                        {{ $bar['value'] }}
                    </span>

                    {{-- Batang (HOOK .isi-batang) --}}
                    <div class="isi-batang animate-bar w-full rounded-t-lg bg-gradient-to-t from-mawar-700
                                to-mawar-400 transition-all duration-300
                                group-hover:from-mawar-800 group-hover:to-mawar-600"
                         style="height: {{ $tinggi }}%; animation-delay: {{ $i * 80 }}ms"></div>

                    {{-- Tooltip --}}
                    <span class="pointer-events-none absolute left-1/2 top-0 -translate-x-1/2 translate-y-[-130%]
                                 whitespace-nowrap rounded-lg bg-mawar-900 px-2 py-1 text-[10px]
                                 font-semibold text-white opacity-0 shadow-lg transition group-hover:opacity-100">
                        {{ $bar['value'] }} {{ $suffix }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Label sumbu X --}}
        <div class="mt-3 flex gap-3">
            @foreach ($bars as $bar)
                <span class="flex-1 truncate text-center text-[10px] font-medium leading-tight text-slate-400
                             transition group-hover/card:text-slate-600"
                      title="{{ $bar['label'] }}">
                    {{ $bar['label'] }}
                </span>
            @endforeach
        </div>
    </div>
</div>