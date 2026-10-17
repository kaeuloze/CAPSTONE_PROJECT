{{-- Pemeriksaan — Tidak Ada Kegiatan Aktif (Figma 237:1558; proposal Gambar 2.47). --}}
@extends('layouts.app')

@section('judul', 'Kelola Pemeriksaan')
@section('subjudul', 'Pemeriksaan hanya bisa dilakukan saat ada kegiatan posyandu yang sedang berlangsung')

@section('isi')
    <div class="kosong-besar kosong-tengah">
        <span class="ikon-bulat" aria-hidden="true"></span>
        <h2>Belum ada kegiatan posyandu yang aktif</h2>
        <p>Daftar warga untuk pemeriksaan hanya muncul jika ada kegiatan posyandu yang sedang berlangsung. Saat ini tidak ada kegiatan aktif, sehingga belum ada warga yang bisa diperiksa.</p>
        <div class="info" style="margin:0 auto 20px;max-width:560px;justify-content:center">Buka menu Kegiatan lalu buat atau mulai satu kegiatan agar warga yang hadir dapat diperiksa.</div>
        <a href="{{ route('kegiatan.index') }}" class="tombol">Pergi ke Kegiatan</a>
    </div>
@endsection
