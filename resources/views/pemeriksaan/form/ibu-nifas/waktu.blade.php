{{-- "Waktu ke Posyandu" setelah bersalin (kolom Kartu Bantu Ibu Nifas/Menyusui; disetujui ditambahkan). --}}
<h2>{{ $bagian->judul }}</h2>
@include('pemeriksaan.partials.isian', ['kolom' => 'usia_kehamilan_minggu', 'label' => 'Waktu setelah bersalin', 'wajib' => true, 'tipe' => 'select',
    'placeholder' => 'Pilih waktu', 'pilihan' => \App\Services\Pemeriksaan\Form\FormIbuNifas::WAKTU_NIFAS])
