{{-- "Penyuluhan" + Rujukan (Figma). Rujukan hanya ke Puskesmas (keputusan 15). --}}
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Tema edukasi yang diberikan</p>
<div class="isian" style="max-width:none">
    @include('pemeriksaan.partials.isian', ['kolom' => 'topik_penyuluhan', 'label' => 'Topik penyuluhan', 'tipe' => 'text', 'placeholder' => $placeholder ?? 'mis. gizi seimbang / kesehatan reproduksi remaja'])
</div>
@include('pemeriksaan.partials.centang', ['kolom' => 'dirujuk', 'label' => 'Rujukan ke Puskesmas (bila ada indikasi risiko hasil pemeriksaan dan skrining)'])
