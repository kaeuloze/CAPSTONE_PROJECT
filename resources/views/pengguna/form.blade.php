{{-- Tambah / Edit Akun Kader (Figma 141:2478, Gambar 2.68; Activity Diagram 2.10 & 2.12). --}}
@extends('layouts.app')

@php
    $modeEdit = $akun->exists;
    $sandiMin = (int) config('posyandu.pengguna.sandi_min');
    $sandiMaks = (int) config('posyandu.pengguna.sandi_maks');
@endphp

@section('judul', 'Tambah / Edit Akun Kader')
@section('subjudul', $modeEdit ? 'Ubah data akun petugas yang dapat mengakses sistem' : 'Buat akun petugas baru yang dapat mengakses sistem')
@section('aksi-kepala')
    <a href="{{ route('pengguna.index') }}" class="tombol">← Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">
            Akun belum bisa disimpan. Periksa kembali username dan kata sandi:
            <ul>@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $modeEdit ? route('pengguna.update', $akun) : route('pengguna.store') }}"
          data-memuat="Menyimpan..." id="form-pengguna" novalidate>
        @csrf
        @if ($modeEdit)
            @method('PUT')
            {{-- Penanda versi data saat formulir dibuka (pengaman edit bersamaan antar-kader). --}}
            <input type="hidden" name="versi" value="{{ old('versi', $akun->updated_at?->format('Y-m-d H:i:s')) }}">
        @endif

        <section class="kartu">
            <h2>Data Akun Petugas</h2>
            <div style="max-width:640px">
                <div @class(['salah' => $errors->has('nama_kader')]) style="margin-bottom:16px">
                    <label for="nama_kader">Nama kader</label>
                    <input type="text" id="nama_kader" name="nama_kader" value="{{ old('nama_kader', $akun->nama_kader) }}" maxlength="100" required autocomplete="off">
                    @error('nama_kader')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('username')]) style="margin-bottom:16px">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username', $akun->username) }}" minlength="3" maxlength="50"
                           pattern="[a-z0-9._]+" autocapitalize="none" spellcheck="false" required autocomplete="off">
                    <p class="ket" style="margin:6px 0 0">Huruf kecil, angka, titik (.), atau garis bawah (_); 3–50 karakter, tanpa spasi.</p>
                    @error('username')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('password')]) style="margin-bottom:16px">
                    <label for="password">Kata sandi</label>
                    <input type="password" id="password" name="password" minlength="{{ $sandiMin }}" maxlength="{{ $sandiMaks }}"
                           @required(! $modeEdit) autocomplete="new-password">
                    <p class="ket" style="margin:6px 0 0">
                        Minimal {{ $sandiMin }} karakter. Boleh berupa kalimat dengan spasi agar mudah diingat.
                        @if ($modeEdit) Kosongkan bila kata sandi tidak diubah. @endif
                        Kata sandi diisi sendiri oleh kader pemilik akun.
                    </p>
                    @error('password')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="password_confirmation">Konfirmasi kata sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" maxlength="{{ $sandiMaks }}"
                           @required(! $modeEdit) autocomplete="new-password">
                </div>
            </div>
        </section>

        <div class="aksi-form">
            <button type="submit" class="tombol abu">Simpan Akun</button>
            <a href="{{ route('pengguna.index') }}" class="tombol">Batal</a>
        </div>
    </form>
@endsection
