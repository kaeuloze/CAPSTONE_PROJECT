{{-- Dashboard SEMENTARA. Isi Dashboard (grafik IMT & PTM) dibuat pada modul Dashboard. --}}
@extends('layouts.app')

@section('judul', 'Dashboard')

@section('isi')
    <p>Selamat datang, <strong>{{ auth()->user()->nama_kader }}</strong>. Isi Dashboard akan dibuat pada modul Dashboard.</p>
@endsection
