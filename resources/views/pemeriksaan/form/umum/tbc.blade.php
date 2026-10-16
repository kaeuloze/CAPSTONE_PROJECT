{{-- "Skrining Gejala TBC" (Figma). Berisiko bila ≥ 2 gejala "Ya" (disetujui). --}}
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Normal jika kurang dari {{ config('posyandu.ambang.tbc_minimal_gejala') }} gejala "Ya". Jika ≥{{ config('posyandu.ambang.tbc_minimal_gejala') }} gejala "Ya" → berisiko, rujuk Puskesmas. (Y = Ya, T = Tidak) Centang untuk "Ya"; jika tidak dicentang maka otomatis "Tidak" saat tersimpan.</p>
@include('pemeriksaan.partials.centang', ['kolom' => 'batuk_terus_menerus', 'label' => 'Batuk terus menerus'])
@include('pemeriksaan.partials.centang', ['kolom' => 'demam_2_minggu', 'label' => 'Demam ≥ 2 minggu'])
@include('pemeriksaan.partials.centang', ['kolom' => 'bb_tidak_stabil', 'label' => 'BB tidak naik atau turun dalam 2 bulan berturut-turut'])
@include('pemeriksaan.partials.centang', ['kolom' => 'kontak_pasien_tbc', 'label' => 'Kontak erat Pasien TBC'])
