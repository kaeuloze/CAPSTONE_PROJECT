{{--
    Satu isian formulir pemeriksaan.
    Wajib: $kunci (bagian), $kolom, $label, $isi (nilai tersimpan per kolom).
    Opsional: $wajib (bool), $tipe ('number'|'text'|'date'|'select'), $pilihan ([nilai => label]),
              $satuan, $min, $max, $step, $placeholder.
--}}
@php
    $nama = "bagian[{$kunci}][{$kolom}]";
    $kunciGalat = "bagian.{$kunci}.{$kolom}";
    $nilaiIsian = old($kunciGalat, $isi[$kolom] ?? null);
    $id = "isian-{$kunci}-{$kolom}";
    $tipe = $tipe ?? 'number';
@endphp
<div @class(['isian', 'salah' => $errors->has($kunciGalat)])>
    <label for="{{ $id }}">{{ $label }} @if ($wajib ?? false)<span class="wajib" aria-hidden="true">*</span>@endif</label>
    @if ($tipe === 'select')
        <select id="{{ $id }}" name="{{ $nama }}" @if ($wajib ?? false) data-wajib @endif>
            <option value="">{{ $placeholder ?? 'Pilih' }}</option>
            @foreach ($pilihan as $nilai => $teks)
                <option value="{{ $nilai }}" @selected((string) $nilaiIsian === (string) $nilai)>{{ $teks }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $tipe }}" id="{{ $id }}" name="{{ $nama }}" value="{{ $nilaiIsian }}"
               @if ($tipe === 'number') inputmode="decimal" step="{{ $step ?? 'any' }}" @isset($min) min="{{ $min }}" @endisset @isset($max) max="{{ $max }}" @endisset @endif
               @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
               @if ($wajib ?? false) data-wajib aria-required="true" @endif autocomplete="off">
    @endif
    @error($kunciGalat)<div class="galat-isian">{{ $message }}</div>@enderror
</div>
