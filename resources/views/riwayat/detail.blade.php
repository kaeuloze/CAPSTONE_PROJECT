{{--
    Detail Pemeriksaan (Figma 141:3062): rincian hasil ukur & screening, hanya-baca, dengan tombol Edit & Hapus.
    Variabel: lihat RiwayatController::dataFormulir().
--}}
@extends('layouts.app')

@section('judul', 'Detail Pemeriksaan — '.$pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y'))
@section('subjudul', 'Rincian hasil ukur & screening. Di sini data bisa dikoreksi (edit/hapus).')
@section('aksi-kepala')
    <a href="{{ route('riwayat.warga', $warga) }}" class="tombol">Kembali</a>
@endsection

@section('isi')
    @include('pemeriksaan.partials.identitas', ['keterangan' => "Kegiatan: {$kegiatan->nama_kegiatan} · Jenis: ".collect($pemeriksaan->jenis_pemeriksaan)->map->label()->implode(', ')])

    @if ($definisi)
        <div id="form-pemeriksaan" class="form-hanya-baca">
            @include('pemeriksaan.partials.isi-form', ['hanyaBaca' => true])
        </div>

        <div class="aksi-form" style="margin-top:16px">
            <button type="button" class="tombol bahaya" data-buka-dialog="dialog-hapus">Hapus</button>
            <a href="{{ route('riwayat.edit', $pemeriksaan) }}" class="tombol utama">Edit</a>
        </div>
    @else
        <div class="pesan info-arsip">Tampilan detail untuk kategori {{ $kategori->label() }} sedang disiapkan bersama formulirnya.</div>
    @endif

    <div class="info">Catatan: koreksi data hasil pemeriksaan yang sudah berhasil disimpan dilakukan di sini — bukan di menu Pemeriksaan.</div>

    <dialog id="dialog-hapus" aria-labelledby="judul-hapus">
        <h3 id="judul-hapus">Hapus data pemeriksaan ini?</h3>
        <p><strong>{{ $warga->nama_warga }}</strong> — {{ $pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y') }}<br>
            Seluruh isian pemeriksaan ini akan dihapus dan tidak bisa dikembalikan.
            @if ($kegiatan->status_kegiatan)
                Karena kegiatannya masih berlangsung, warga akan muncul kembali di menu Pemeriksaan.
            @endif
        </p>
        <form method="POST" action="{{ route('riwayat.destroy', $pemeriksaan) }}" data-memuat="Menghapus...">
            @csrf
            @method('DELETE')
            <div class="dialog-tombol">
                <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                <button type="submit" class="tombol utama">Ya, Hapus</button>
            </div>
        </form>
    </dialog>
@endsection

@push('skrip')
    <script src="{{ asset('js/pemeriksaan-form.js') }}"></script>
@endpush
