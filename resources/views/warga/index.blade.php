{{-- Kelola Data Warga (wireframe Gambar 2.43; Activity Diagram 2.29 & 2.32). --}}
@extends('layouts.app')

@section('judul', 'Kelola Data Warga')
@section('subjudul', 'Buku induk warga — badge kategori usia muncul otomatis dari tanggal lahir')
@section('aksi-kepala')
    <a href="{{ route('warga.create') }}" class="tombol abu">+ Tambah Warga</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Pencarian Nama/NIK & filter kategori usia (SKF-04). --}}
    <form method="GET" action="{{ route('warga.index') }}" class="saring" role="search">
        <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama / NIK..." aria-label="Cari nama atau NIK">
        <select name="kategori" aria-label="Kategori Usia" onchange="this.form.submit()">
            <option value="">Kategori Usia (semua)</option>
            @foreach (\App\Enums\KategoriUsia::options() as $nilai => $label)
                <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="tombol">Cari</button>
    </form>

    <div class="bungkus-tabel">
        <table>
            <thead>
                <tr>
                    <th scope="col" style="width:56px">No</th>
                    <th scope="col">Nama</th>
                    <th scope="col">NIK</th>
                    <th scope="col">Tgl Lahir</th>
                    <th scope="col">Kategori Usia</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarWarga as $warga)
                    <tr>
                        <td>{{ $daftarWarga->firstItem() + $loop->index }}</td>
                        <td>{{ $warga->nama_warga }}</td>
                        <td>{{ $warga->nik }}</td>
                        <td>{{ $warga->tanggal_lahir->translatedFormat('d-m-Y') }}</td>
                        <td>@include('partials.badge-warga', ['warga' => $warga])</td>
                        <td>
                            <div class="aksi">
                                <a href="{{ route('warga.show', $warga) }}" class="tombol kecil">Lihat</a>
                                <a href="{{ route('warga.edit', $warga) }}" class="tombol kecil">Edit</a>
                                <button type="button" class="tombol kecil bahaya" data-buka-dialog="dialog-hapus"
                                        data-aksi="{{ route('warga.destroy', $warga) }}" data-nama="{{ $warga->nama_warga }}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="kosong">
                            @if ($cari !== '' || $kategoriDipilih)
                                Tidak ada warga yang cocok dengan pencarian atau filter.
                            @else
                                Belum ada data warga. Klik "+ Tambah Warga" untuk mendaftarkan warga.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftarWarga, 'satuan' => 'warga'])

    <div class="info">Input wajib: Nama, NIK, Alamat, Tanggal Lahir. Sistem membaca Tgl Lahir lalu menempelkan badge kategori (Balita/Remaja/Dewasa/Lansia) otomatis. Status khusus (Bumil/Nifas/Menyusui) ditandai manual.</div>

    {{-- Dialog Konfirmasi Hapus (wireframe Gambar 2.73). --}}
    <dialog id="dialog-hapus" aria-labelledby="judul-hapus">
        <h3 id="judul-hapus">Hapus data warga ini?</h3>
        <p><strong data-isi-nama></strong><br>Data warga yang sudah dihapus tidak bisa dikembalikan.</p>
        <form method="POST" action="" data-memuat="Menghapus...">
            @csrf
            @method('DELETE')
            <div class="dialog-tombol">
                <button type="button" class="tombol" data-tutup-dialog>Batal</button>
                <button type="submit" class="tombol utama">Ya, Hapus</button>
            </div>
        </form>
    </dialog>
@endsection
