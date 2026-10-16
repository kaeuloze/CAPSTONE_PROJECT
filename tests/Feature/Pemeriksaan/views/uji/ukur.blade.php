<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'berat_badan', 'label' => 'Berat badan (kg)', 'wajib' => true])
@include('pemeriksaan.partials.isian', ['kolom' => 'tinggi_badan', 'label' => 'Tinggi badan (cm)', 'wajib' => true])
