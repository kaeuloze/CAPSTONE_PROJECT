{{-- Detail dan Edit Kegiatan (wireframe 2.41; Activity Diagram 2.8 & 2.9). --}}
@extends('layouts.app')

@php $status = (string) old('status_kegiatan', $kegiatan->status_kegiatan ? '1' : '0'); @endphp

@section('judul', 'Detail Kegiatan')
@section('subjudul', 'Pengubahan status otomatis berubah saat dilihat di tampilan daftar kegiatan')
@section('aksi-kepala')
    <a href="{{ route('kegiatan.index', ['tab' => $kegiatan->status_kegiatan ? 'berlangsung' : 'arsip']) }}" class="tombol">← Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">
            Perubahan belum bisa disimpan:
            <ul>@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('kegiatan.update', $kegiatan) }}" data-memuat="Menyimpan..." novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="versi" value="{{ old('versi', $kegiatan->updated_at?->format('Y-m-d H:i:s')) }}">

        <section class="kartu">
            <h2>Detail Kegiatan</h2>
            <div style="max-width:680px">
                <div @class(['salah' => $errors->has('nama_kegiatan')]) style="margin-bottom:16px">
                    <label for="nama_kegiatan">Nama kegiatan</label>
                    <input type="text" id="nama_kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}" maxlength="100" required>
                    @error('nama_kegiatan')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('tanggal_kegiatan')]) style="margin-bottom:16px">
                    <label for="tanggal_kegiatan">Tanggal pelaksanaan</label>
                    <input type="date" id="tanggal_kegiatan" name="tanggal_kegiatan" value="{{ old('tanggal_kegiatan', $kegiatan->tanggal_kegiatan->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" required>
                    @error('tanggal_kegiatan')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <fieldset style="border:0;padding:0;margin:0">
                    <legend class="label">Status (Selesai / Berlangsung)</legend>
                    <div class="segmen">
                        <label><input type="radio" name="status_kegiatan" value="0" @checked($status === '0')><span>Selesai</span></label>
                        <label><input type="radio" name="status_kegiatan" value="1" @checked($status === '1')><span>Berlangsung</span></label>
                    </div>
                    @error('status_kegiatan')<div class="galat-isian">{{ $message }}</div>@enderror
                </fieldset>
                <p class="ket" style="margin:12px 0 0">
                    @if ($kegiatan->status_kegiatan)
                        Otomatis selesai pada {{ $kegiatan->batasWaktu()->locale('id')->translatedFormat('l, j F Y H:i') }} WIB. Memilih "Selesai" akan mencatat warga yang belum hadir sebagai Tidak Hadir.
                    @else
                        Memilih "Berlangsung" akan membuka kembali kegiatan ini selama {{ config('posyandu.kegiatan.durasi_jam') }} jam (hanya bila tidak ada kegiatan lain yang berlangsung).
                    @endif
                </p>
            </div>
        </section>

        <div class="aksi-form" style="margin-bottom:20px">
            <button type="submit" class="tombol abu">Simpan</button>
            <a href="{{ route('kegiatan.index', ['tab' => $kegiatan->status_kegiatan ? 'berlangsung' : 'arsip']) }}" class="tombol">Batal</a>
        </div>
    </form>

    <section class="kartu">
        <h2>Hapus Kegiatan ini</h2>
        <p class="ket">Kegiatan hanya bisa dihapus bila belum ada warga yang hadir, diperiksa, atau divaksin.</p>
        <button type="button" class="tombol abu" data-buka-dialog="dialog-hapus" data-aksi="{{ route('kegiatan.destroy', $kegiatan) }}" data-nama="{{ $kegiatan->nama_kegiatan }}">Hapus</button>
    </section>

    {{-- Dialog Konfirmasi Hapus (wireframe 2.73). --}}
    <dialog id="dialog-hapus" aria-labelledby="judul-hapus">
        <h3 id="judul-hapus">Hapus kegiatan ini?</h3>
        <p><strong data-isi-nama></strong><br>Kegiatan yang sudah dihapus tidak bisa dikembalikan.</p>
        <form method="POST" action="" data-memuat="Menghapus...">
            @csrf
            @method('DELETE')
            <div class="dialog-tombol">
                <button type="button" class="tombol" data-tutup-dialog>Tidak</button>
                <button type="submit" class="tombol utama">Ya, Hapus</button>
            </div>
        </form>
    </dialog>
@endsection
