{{--
    Isian centang Ya/Tidak (Figma: dicentang = "Ya", tidak dicentang = "Tidak" saat tersimpan).
    Wajib: $kunci (bagian), $kolom, $label, $isi.
--}}
@php
    $kunciGalat = "bagian.{$kunci}.{$kolom}";
    $dicentang = (string) old($kunciGalat, $isi[$kolom] ?? '0') === '1';
@endphp
<label class="baris-centang">
    <span>{{ $label }}</span>
    <input type="hidden" name="bagian[{{ $kunci }}][{{ $kolom }}]" value="0">
    <input type="checkbox" name="bagian[{{ $kunci }}][{{ $kolom }}]" value="1" @checked($dicentang)>
</label>
