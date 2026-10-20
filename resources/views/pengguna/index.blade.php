{{-- Kelola Data Akun / Manajemen Pengguna (Figma 141:164, Gambar 2.67; Activity Diagram 2.11 & 2.13). --}}
@extends('layouts.app')

@section('judul', 'Kelola Data Akun')
@section('subjudul', 'Kelola akun kader/petugas yang punya akses sistem')
@section('aksi-kepala')
    <a href="{{ route('pengguna.create') }}" class="tombol abu">+ Tambah Akun</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('pengguna.index') }}" class="saring" role="search">
        <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama kader / username..." aria-label="Cari nama kader atau username">
        <button type="submit" class="tombol">Cari</button>
    </form>

    <div class="bungkus-tabel">
        <table>
            <thead>
                <tr>
                    <th scope="col" style="width:56px">No</th>
                    <th scope="col">Nama Kader</th>
                    <th scope="col">Username</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarAkun as $akun)
                    <tr>
                        <td>{{ $daftarAkun->firstItem() + $loop->index }}</td>
                        <td>
                            {{ $akun->nama_kader }}
                            @if ($akun->id_kader === auth()->id())
                                <span class="badge">Akun Anda</span>
                            @endif
                        </td>
                        <td>{{ $akun->username }}</td>
                        <td>
                            <div class="aksi">
                                <a href="{{ route('pengguna.edit', $akun) }}" class="tombol kecil">Edit</a>
                                <button type="button" class="tombol kecil bahaya" data-buka-dialog="dialog-hapus"
                                        data-aksi="{{ route('pengguna.destroy', $akun) }}"
                                        data-nama="{{ $akun->nama_kader }} ({{ $akun->username }}){{ $akun->id_kader === auth()->id() ? ' — akun yang sedang Anda pakai; Anda akan keluar' : '' }}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="kosong">
                            {{ $cari !== '' ? 'Tidak ada akun yang cocok dengan pencarian.' : 'Belum ada akun kader.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftarAkun, 'satuan' => 'akun'])

    <div class="info">Mendukung konkurensi: beberapa kader login dengan akun masing-masing dan bekerja di perangkat berbeda secara bersamaan.</div>

    {{-- Dialog Konfirmasi Hapus (Gambar 2.73; Activity Diagram 2.13 "Yakin hapus?"). --}}
    <dialog id="dialog-hapus" aria-labelledby="judul-hapus">
        <h3 id="judul-hapus">Yakin hapus akun ini?</h3>
        <p><strong data-isi-nama></strong><br>Akun yang sudah dihapus tidak bisa dipakai untuk masuk lagi dan tidak bisa dikembalikan.</p>
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
