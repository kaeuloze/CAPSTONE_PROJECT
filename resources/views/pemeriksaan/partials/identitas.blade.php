{{-- Kartu identitas warga + keterangan kehadiran (Figma form "hasil rev"). Variabel: $warga, $kategori, $usia. --}}
<section class="kartu identitas-periksa">
    <span class="avatar" aria-hidden="true"></span>
    <div>
        <strong>{{ $warga->nama_warga }}</strong>
        <small>NIK {{ $warga->nik }}</small>
    </div>
    <span class="badge">{{ $kategori->label() }} · {{ $usia }} th</span>
</section>
<div class="info" style="margin:0 0 16px">Kehadiran: Hadir (tercatat dari presensi kegiatan)</div>
