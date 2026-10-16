{{-- Buat Kegiatan Baru (wireframe 2.40; Activity Diagram 2.6). --}}
@extends('layouts.app')

@section('judul', 'Buat Kegiatan Baru')
@section('subjudul', 'Jadwalkan pelaksanaan posyandu baru')
@section('aksi-kepala')
    <a href="{{ route('kegiatan.index') }}" class="tombol">← Kembali</a>
@endsection

@section('isi')
    @if ($kegiatanAktif)
        <div class="pesan galat" role="alert">
            Masih ada kegiatan yang berlangsung: <strong>{{ $kegiatanAktif->nama_kegiatan }}</strong>.
            Selesaikan kegiatan tersebut terlebih dahulu melalui halaman
            <a href="{{ route('presensi.index', $kegiatanAktif) }}">Presensi</a>.
        </div>
    @endif

    @if ($errors->any())
        <div class="pesan galat" role="alert">
            Kegiatan belum bisa dibuat. Periksa kembali isian berikut:
            <ul>@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('kegiatan.store') }}" data-memuat="Membuat kegiatan..." novalidate>
        @csrf
        <section class="kartu" style="max-width:none">
            <h2>Detail Kegiatan</h2>
            <div style="max-width:680px">
                <div @class(['salah' => $errors->has('nama_kegiatan')]) style="margin-bottom:16px">
                    <label for="nama_kegiatan">Nama kegiatan (mis. Posyandu Mei 2026)</label>
                    <input type="text" id="nama_kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}" maxlength="100" placeholder="mis. Posyandu {{ now()->locale('id')->translatedFormat('F Y') }}" required>
                    @error('nama_kegiatan')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('tanggal_kegiatan')])>
                    <label for="tanggal_kegiatan">Tanggal pelaksanaan</label>
                    <input type="date" id="tanggal_kegiatan" name="tanggal_kegiatan" value="{{ old('tanggal_kegiatan', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @error('tanggal_kegiatan')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div class="aksi-form">
            <button type="submit" class="tombol abu" @disabled($kegiatanAktif)>Buat &amp; Mulai Kegiatan</button>
            <a href="{{ route('kegiatan.index') }}" class="tombol">Batal</a>
        </div>
    </form>
@endsection
