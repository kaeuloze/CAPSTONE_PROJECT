{{-- "Hasil dihitung otomatis (Kalkulator IMT)" (Figma Remaja 418:65, Dewasa 430:65) — tampil di tab Rutin Bulanan. --}}
<section class="info hasil-otomatis" id="hasil-otomatis" data-hanya-tab="rutin" aria-live="polite"
         data-ambang="{{ json_encode(\App\Services\Pemeriksaan\Kalkulator::untukLayar()) }}"
         data-laki-laki="{{ $warga->jenis_kelamin ? '1' : '0' }}">
    <div>
        <strong>Hasil dihitung otomatis (Kalkulator IMT)</strong>
        <dl>
            <dt>IMT</dt><dd id="hasil-imt">-</dd>
            <dt>Tekanan darah</dt><dd id="hasil-td">-</dd>
            <dt>Lingkar perut</dt><dd id="hasil-lp">-</dd>
            @if (in_array('kadar_gula_darah', $definisi->bagian()['pengukuran']->kolom()))
                <dt>Gula darah (GDS)</dt><dd id="hasil-gds">-</dd>
            @endif
            <dt>Gejala TBC</dt><dd id="hasil-tbc">-</dd>
        </dl>
    </div>
</section>
@push('skrip')
    <script src="{{ asset('js/pemeriksaan-kalkulator.js') }}"></script>
@endpush
