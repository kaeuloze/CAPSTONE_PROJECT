@php
    $kunciGalat = "bagian.{$kunci}.{$kolom}";
    $dicentang  = (string) old($kunciGalat, $isi[$kolom] ?? '0') === '1';
    $modeBaca ??= false;
@endphp

@if ($modeBaca)
    {{-- Detail Riwayat: centang = "Ya", tidak dicentang = "Tidak" (sama seperti keterangan di formulir). --}}
    @include('pemeriksaan.partials.baris-baca', ['teks' => (string) ($isi[$kolom] ?? '0') === '1' ? 'Ya' : 'Tidak'])
@else
<label class="baris-centang @if ($errors->has($kunciGalat)) salah @endif">
    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>

    <input type="hidden" name="bagian[{{ $kunci }}][{{ $kolom }}]" value="0">
    <input type="checkbox" name="bagian[{{ $kunci }}][{{ $kolom }}]" value="1" @checked($dicentang)>
</label>

@error($kunciGalat)
    <p class="galat-isian">{{ $message }}</p>
@enderror
@endif
