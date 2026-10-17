{{-- "Skrining Penimbangan dan Pengukuran" ibu hamil: BB, LILA, TD (kartu kertas; tanpa TB). Ploting LILA & TD otomatis (disetujui). --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'berat_badan', 'label' => 'Berat badan (kg)', 'wajib' => true, 'min' => 1, 'max' => 300, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'lila', 'label' => 'LILA (cm)', 'wajib' => true, 'min' => 5, 'max' => 60, 'step' => '0.1'])
@include('pemeriksaan.partials.tekanan-darah', ['wajib' => true])
<p class="ket">Ploting LILA (Normal ≥ 23,5 cm / KEK &lt; 23,5 cm) dan ploting tekanan darah (Normal &lt; 130/85 / Risiko ≥ 130/85) dihitung otomatis.</p>
