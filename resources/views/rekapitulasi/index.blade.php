{{-- Rekapitulasi — Halaman Utama / Hub (Figma 306:33, Gambar 2.69). Data: $daftarKategori, $periodeBawaan. --}}
@extends('layouts.app')

@php
    $keterangan = [
        'remaja' => 'Usia · IMT, LP, TD, HB, penglihatan, pendengaran',
        'ibu_hamil_nifas' => 'Pemeriksaan kehamilan, nifas, dan menyusui',
        'dewasa_lansia' => 'IMT, LP, tekanan darah, gula darah, TBC, PUMA',
    ];
@endphp

@section('judul', 'Rekapitulasi')
@section('subjudul', 'Pilih jenis rekapitulasi yang ingin dilihat. Setiap rekap mengikuti format resmi Kemenkes dan terisi otomatis dari hasil pemeriksaan.')

@section('isi')
    <h2 style="font-size:15px;margin:0 0 16px">Pilih Kategori Rekapitulasi</h2>
    <div class="kartu-rekap">
        @foreach ($daftarKategori as $kategori)
            <a href="{{ route('rekapitulasi.show', $kategori) }}" class="kartu">
                <strong>{{ $kategori->label() }}</strong>
                <span class="ket">{{ $keterangan[$kategori->value] }}</span>
            </a>
        @endforeach
    </div>

    <div class="info">Menekan sebuah kartu membuka tabel rekap kategori tersebut (dengan filter Bulan/Tahun). Tampilan awal: periode kegiatan terakhir ({{ $periodeBawaan->label() }}).</div>
@endsection
