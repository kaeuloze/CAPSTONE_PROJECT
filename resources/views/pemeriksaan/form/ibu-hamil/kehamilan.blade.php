{{-- "Pemeriksaan Sesuai Umur Kehamilan" (Figma 430:927; kolom kartu kertas). Variabel: $kunci, $isi, $bagian. --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'usia_kehamilan_minggu', 'label' => 'Usia kehamilan (minggu)', 'wajib' => true, 'tipe' => 'select',
    'placeholder' => 'Pilih rentang minggu', 'pilihan' => \App\Services\Pemeriksaan\Form\FormIbuHamil::USIA_KEHAMILAN])
