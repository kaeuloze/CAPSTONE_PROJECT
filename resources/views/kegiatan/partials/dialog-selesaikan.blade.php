{{-- Dialog Konfirmasi Selesaikan Kegiatan (Figma "hasil rev"). Variabel: $kegiatan, $belumSubmit (jumlah pemeriksaan draft). --}}
<dialog id="dialog-selesaikan" aria-labelledby="judul-selesaikan">
    <h3 id="judul-selesaikan">Selesaikan kegiatan {{ $kegiatan->nama_kegiatan }}?</h3>
    <p>Warga yang belum presensi akan tercatat "Tidak Hadir", dan kegiatan pindah ke tab Arsip Kegiatan.</p>
    @if ($belumSubmit > 0)
        <p style="margin-top:12px">Masih ada <strong>{{ $belumSubmit }} pemeriksaan yang belum di-Submit</strong>. Datanya tetap tersimpan dan dapat dilanjutkan dengan membuka kembali kegiatan dari Arsip.</p>
    @endif
    <form method="POST" action="{{ route('kegiatan.selesaikan', $kegiatan) }}" data-memuat="Menyelesaikan...">
        @csrf
        <div class="dialog-tombol">
            <button type="button" class="tombol" data-tutup-dialog>Batal</button>
            <button type="submit" class="tombol utama">Ya, Selesaikan</button>
        </div>
    </form>
</dialog>
