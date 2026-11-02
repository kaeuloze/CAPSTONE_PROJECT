{{-- Tab Tahunan: Kadar HB khusus lansia perempuan (pengecekan bersama kader 31 Okt 2026). Normal ≥ 12 g/dL. --}}
<h2>{{ $bagian->judul }} (Khusus Lansia Perempuan)</h2>
<p class="ket">Normal bila kadar HB ≥ {{ str_replace('.', ',', (string) config('posyandu.ambang.hb_normal_mulai')) }} g/dL.</p>
@include('pemeriksaan.partials.isian', ['kolom' => 'kadar_hb', 'label' => 'Kadar HB (g/dL)', 'min' => 1, 'max' => 25, 'step' => '0.1'])
