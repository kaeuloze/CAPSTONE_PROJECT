{{-- Badge kategori usia (otomatis dari tanggal lahir) + badge kondisi khusus bila ada. Variabel: $warga --}}
<span class="badge">{{ $warga->kategori_usia_terkini->label() }}</span>
@if ($warga->status_kondisi && $warga->status_kondisi !== \App\Enums\StatusKondisi::Normal)
    <span class="badge khusus">{{ $warga->status_kondisi->label() }}</span>
@endif
