{{-- Vaksinasi Balita — tidak ada kegiatan aktif (pola sama dengan menu Pemeriksaan, Figma 237:1558). --}}
@extends('layouts.app')

@section('judul', 'Kelola Vaksinasi Balita')
@section('subjudul', 'Pencatatan vaksin & ASI hanya bisa dilakukan saat ada kegiatan posyandu yang sedang berlangsung')

@section('isi')
    <div class="kosong-besar kosong-tengah">
        <span class="ikon-bulat" aria-hidden="true"></span>
        <h2>Belum ada kegiatan posyandu yang aktif</h2>
        <p>Daftar balita untuk vaksinasi hanya muncul jika ada kegiatan posyandu yang sedang berlangsung dan balita sudah ditandai Hadir.</p>
        <div class="info" style="margin:0 auto 20px;max-width:560px;justify-content:center">Buka menu Kegiatan lalu buat atau mulai satu kegiatan.</div>
        <a href="{{ route('kegiatan.index') }}" class="tombol">Pergi ke Kegiatan</a>
    </div>
@endsection
