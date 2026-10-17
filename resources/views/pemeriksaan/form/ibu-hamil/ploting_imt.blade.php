{{-- Ploting IMT sesuai kurva Buku KIA — dipilih kader (disetujui, 3 pilihan). --}}
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Lihat kurva di Buku KIA (IMT sebelum hamil = Normal 18,5 – 24,9 / Risiko &lt; 18,5 atau &gt; 25 kg/m²).</p>
@include('pemeriksaan.partials.isian', ['kolom' => 'status_ploting_imt', 'label' => 'Ploting IMT', 'tipe' => 'select',
    'placeholder' => 'Pilih hasil ploting', 'pilihan' => \App\Services\Pemeriksaan\Form\FormIbuHamil::PLOTING_IMT])
