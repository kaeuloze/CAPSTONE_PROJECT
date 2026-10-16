{{-- Form pemeriksaan untuk kategori ini sedang dikembangkan (form dibuat satu per satu, keputusan 39). --}}
@extends('layouts.app')

@section('judul', 'Pemeriksaan — '.$kategori->label())
@section('subjudul', 'Form menyesuaikan kategori usia (dibuka dari menu Pemeriksaan)')
@section('aksi-kepala')
    <a href="{{ route('pemeriksaan.index') }}" class="tombol">← Kembali</a>
@endsection

@section('isi')
    @include('pemeriksaan.partials.identitas')
    <div class="kosong-besar kosong-tengah">
        <h2>Form {{ $kategori->label() }} sedang disiapkan</h2>
        <p>Formulir pemeriksaan untuk kategori ini belum tersedia. Silakan kembali ke daftar pemeriksaan.</p>
        <a href="{{ route('pemeriksaan.index') }}" class="tombol">Kembali ke daftar</a>
    </div>
@endsection
