{{-- Satu kartu bagian formulir. Variabel: $definisi, $kunciBagian, $nilai, $versi, $hanyaBaca. --}}
@php $bagian = $definisi->bagian()[$kunciBagian]; @endphp
<section class="kartu bagian-periksa" data-bagian="{{ $kunciBagian }}">
    @if ($hanyaBaca)
        <fieldset class="bagian-baca" disabled>
            @include($definisi->view().'.'.$kunciBagian, ['kunci' => $kunciBagian, 'isi' => $nilai[$kunciBagian], 'bagian' => $bagian])
        </fieldset>
    @else
        <input type="hidden" name="versi[{{ $kunciBagian }}]" value="{{ old("versi.$kunciBagian", $versi[$kunciBagian]) }}">
        @include($definisi->view().'.'.$kunciBagian, ['kunci' => $kunciBagian, 'isi' => $nilai[$kunciBagian], 'bagian' => $bagian])
    @endif
</section>
