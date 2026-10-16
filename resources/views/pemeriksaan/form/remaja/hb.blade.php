{{-- Tab Tahunan Remaja Putri (Figma 424:168). Normal ≥ 12 g/dL, Risiko < 12 g/dL (format rekap). --}}
<h2>Pemeriksaan</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'kadar_hb', 'label' => 'Kadar HB (g/dL)', 'min' => 1, 'max' => 25, 'step' => '0.1'])
