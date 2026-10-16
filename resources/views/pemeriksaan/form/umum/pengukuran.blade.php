{{-- "Skrining Penimbangan dan Pengukuran" (Figma form "hasil rev"). Variabel: $kunci, $isi, $bagian. --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'berat_badan', 'label' => 'Berat badan (kg)', 'wajib' => in_array('berat_badan', $bagian->wajib), 'min' => 1, 'max' => 300, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'tinggi_badan', 'label' => 'Tinggi Badan (cm)', 'wajib' => in_array('tinggi_badan', $bagian->wajib), 'min' => 40, 'max' => 250, 'step' => '0.1'])
@include('pemeriksaan.partials.isian', ['kolom' => 'lingkar_perut', 'label' => 'Lingkar perut (cm)', 'wajib' => in_array('lingkar_perut', $bagian->wajib), 'min' => 20, 'max' => 200, 'step' => '0.1'])
@include('pemeriksaan.partials.tekanan-darah', ['wajib' => in_array('td_sistolik', $bagian->wajib)])
