{{--
    Kartu identitas warga (Figma form "hasil rev" & Detail Riwayat). Variabel: $warga, $kategori, $usia.
    Opsional: $keterangan (teks di bawah kartu; bawaan = keterangan kehadiran).
--}}
<section class="kartu identitas-periksa">
    <span class="avatar" aria-hidden="true"></span>
    <div>
        <strong>{{ $warga->nama_warga }}</strong>
        <small>NIK {{ $warga->nik }}</small>
    </div>
    <span class="badge">{{ $kategori->label() }} · {{ $usia }} th</span>
</section>
<div class="info" style="margin:0 0 16px">{{ $keterangan ?? 'Kehadiran: Hadir (tercatat dari presensi kegiatan)' }}</div>
