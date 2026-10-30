@php
    $nama = "bagian[{$kunci}][{$kolom}]";
    $kunciGalat = "bagian.{$kunci}.{$kolom}";
    $nilaiIsian = old($kunciGalat, $isi[$kolom] ?? null);
    $id = "isian-{$kunci}-{$kolom}";
    $tipe = $tipe ?? 'number';
    $wajib = $wajib ?? false;
    $modeBaca ??= false;
@endphp

@if ($modeBaca)
    @php
        $isiBaca = $isi[$kolom] ?? null;
        if ($isiBaca !== null && $tipe === 'select') {
            $isiBaca = $pilihan[$isiBaca] ?? $isiBaca;
        } elseif ($isiBaca !== null && $tipe === 'number') {
            $isiBaca = \App\Services\Pemeriksaan\HasilOtomatis::angka($isiBaca);
        }
    @endphp
    @include('pemeriksaan.partials.baris-baca', ['teks' => $isiBaca])
@else

<div @class(['isian', 'salah' => $errors->has($kunciGalat)])>
    <label for="{{ $id }}">
        {{ $label }}
        @if ($satuan ?? null)<span class="text-[11px] font-normal text-slate-400">({{ $satuan }})</span>@endif
        @if ($wajib)<span class="wajib" aria-hidden="true">*</span>@endif
    </label>

    @if ($tipe === 'select')
        <select id="{{ $id }}" name="{{ $nama }}" @if ($wajib) data-wajib @endif>
            <option value="">{{ $placeholder ?? 'Pilih' }}</option>
            @foreach ($pilihan as $nilai => $teks)
                <option value="{{ $nilai }}" @selected((string) $nilaiIsian === (string) $nilai)>{{ $teks }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $tipe }}" id="{{ $id }}" name="{{ $nama }}" value="{{ $nilaiIsian }}"
               @if ($tipe === 'number')
                   inputmode="decimal" step="{{ $step ?? 'any' }}"
                   @isset($min) min="{{ $min }}" @endisset
                   @isset($max) max="{{ $max }}" @endisset
               @endif
               @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
               @if ($wajib) data-wajib aria-required="true" @endif
               autocomplete="off" />
    @endif

    @error($kunciGalat)
        <p class="galat-isian">{{ $message }}</p>
    @enderror
</div>
@endif