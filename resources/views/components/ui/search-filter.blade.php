@props([
    'placeholder' => 'Cari nama atau NIK...',
    'options'     => [],
    'optionLabel' => 'Semua Kategori',
])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center">

    {{-- Pencarian (SKF-04) --}}
    <div class="relative flex-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
        </svg>
        <input type="search" placeholder="{{ $placeholder }}" class="input pl-9" />
    </div>

    {{-- Filter kategori --}}
    <select class="input sm:w-52">
        <option>{{ $optionLabel }}</option>
        @foreach ($options as $opt)
            <option value="{{ is_array($opt) ? $opt['value'] : $opt }}">
                {{ is_array($opt) ? $opt['label'] : $opt }}
            </option>
        @endforeach
    </select>

</div>