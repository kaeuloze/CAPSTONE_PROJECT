{{-- Tab 6 bulan (Figma Remaja 424:65, Dewasa 430:201): Tes Penglihatan (Hitung Jari) & Tes Pendengaran (Berbisik). Pilihan Normal / Gangguan (disetujui). --}}
@php $pilihan = \App\Enums\StatusSensorik::options(); @endphp
<h2>Tes Penglihatan (Hitung Jari)</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'mata_kanan', 'label' => 'Mata Kanan', 'tipe' => 'select', 'pilihan' => $pilihan, 'placeholder' => 'Pilih hasil'])
@include('pemeriksaan.partials.isian', ['kolom' => 'mata_kiri', 'label' => 'Mata Kiri', 'tipe' => 'select', 'pilihan' => $pilihan, 'placeholder' => 'Pilih hasil'])
<h2 style="margin-top:24px">Tes Pendengaran (Berbisik)</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'telinga_kanan', 'label' => 'Telinga Kanan', 'tipe' => 'select', 'pilihan' => $pilihan, 'placeholder' => 'Pilih hasil'])
@include('pemeriksaan.partials.isian', ['kolom' => 'telinga_kiri', 'label' => 'Telinga Kiri', 'tipe' => 'select', 'pilihan' => $pilihan, 'placeholder' => 'Pilih hasil'])
