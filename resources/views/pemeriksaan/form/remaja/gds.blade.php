{{-- Gula Darah Sewaktu remaja usia ≥ 15 tahun, wajib (pengecekan bersama kader 31 Okt 2026). --}}
<h2>{{ $bagian->judul }} (Usia ≥ {{ config('posyandu.ambang.gula_darah_usia_minimal') }} Tahun)</h2>
<p class="ket">Berisiko bila ≥ {{ config('posyandu.ambang.gula_darah_berisiko') }} mg/dl. Boleh diisi nakes atau kader yang berpengalaman.</p>
@include('pemeriksaan.partials.isian', ['kolom' => 'kadar_gula_darah', 'label' => 'Kadar gula darah sewaktu (mg/dl)', 'wajib' => true, 'min' => 20, 'max' => 600, 'step' => '0.1'])
