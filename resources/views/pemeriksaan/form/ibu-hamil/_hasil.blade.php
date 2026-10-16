{{-- Hasil otomatis ibu hamil: ploting LILA & TD (disetujui otomatis) dan gejala TBC. --}}
<section class="info hasil-otomatis" id="hasil-otomatis" data-hanya-tab="hamil" aria-live="polite"
         data-ambang="{{ json_encode(\App\Services\Pemeriksaan\Kalkulator::untukLayar()) }}" data-laki-laki="0">
    <div>
        <strong>Hasil dihitung otomatis</strong>
        <dl>
            <dt>Ploting LILA</dt><dd id="hasil-lila">-</dd>
            <dt>Ploting tekanan darah</dt><dd id="hasil-td">-</dd>
            <dt>Gejala TBC</dt><dd id="hasil-tbc">-</dd>
        </dl>
    </div>
</section>
@push('skrip')
    <script src="{{ asset('js/pemeriksaan-kalkulator.js') }}"></script>
@endpush
