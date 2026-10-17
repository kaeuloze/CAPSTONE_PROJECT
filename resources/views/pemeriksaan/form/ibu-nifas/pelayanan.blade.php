{{-- "Pelayanan Kesehatan" ibu nifas (kartu kertas: jumlah kapsul vitamin A = angka). --}}
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Centang untuk "Ya"; jika tidak dicentang maka otomatis "Tidak" saat tersimpan.</p>
@include('pemeriksaan.partials.isian', ['kolom' => 'jumlah_kapsul_vit_a', 'label' => 'Jika nakes memberikan vitamin A, tuliskan jumlah kapsul', 'min' => 0, 'max' => 10, 'step' => '1'])
@include('pemeriksaan.partials.centang', ['kolom' => 'status_rutin_vit_a', 'label' => 'Rutin konsumsi vitamin A'])
@include('pemeriksaan.partials.centang', ['kolom' => 'status_menyusui', 'label' => 'Menyusui'])
@include('pemeriksaan.partials.centang', ['kolom' => 'status_kb_pascasalin', 'label' => 'KB pasca persalinan'])
