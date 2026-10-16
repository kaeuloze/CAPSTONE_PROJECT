{{-- "Skrining Penimbangan dan Pengukuran" ibu nifas: BB & TD (kartu kertas; tanpa TB/LILA). Ploting TD otomatis (disetujui). --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'berat_badan', 'label' => 'Berat badan (kg)', 'wajib' => true, 'min' => 1, 'max' => 300, 'step' => '0.1'])
@include('pemeriksaan.partials.tekanan-darah', ['wajib' => true])
<p class="ket">Ploting tekanan darah (Normal &lt; 130/85 / Risiko ≥ 130/85) dihitung otomatis.</p>
