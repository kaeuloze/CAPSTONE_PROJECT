{{-- "Skrining Penimbangan dan Pengukuran" ibu hamil: BB, TB, lingkar perut, LILA, TD. TB & lingkar perut hanya dicatat (pengecekan kader 31 Okt 2026). Ploting LILA & TD otomatis (disetujui). --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'berat_badan', 'label' => 'Berat badan (kg)', 'wajib' => true, 'min' => 1, 'max' => 300, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'tinggi_badan', 'label' => 'Tinggi Badan (cm)', 'wajib' => true, 'min' => 40, 'max' => 250, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'lingkar_perut', 'label' => 'Lingkar perut (cm)', 'wajib' => true, 'min' => 20, 'max' => 200, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'lila', 'label' => 'LILA (cm)', 'wajib' => true, 'min' => 5, 'max' => 60, 'step' => '0.1'])
@include('pemeriksaan.partials.tekanan-darah', ['wajib' => true])
<p class="ket">Ploting LILA (Normal ≥ 23,5 cm / KEK &lt; 23,5 cm) dan ploting tekanan darah (Normal {{ \App\Services\Pemeriksaan\Kalkulator::labelTekananDarah('dewasa')['normal'] }} / Risiko {{ \App\Services\Pemeriksaan\Kalkulator::labelTekananDarah('dewasa')['risiko'] }} mmHg) dihitung otomatis. Tinggi badan dan lingkar perut hanya dicatat.</p>
