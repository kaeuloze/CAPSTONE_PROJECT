{{-- Daftar Warga Riwayat Pemeriksaan (Figma 198:723). --}}
@extends('layouts.app')

@section('judul', 'Daftar Warga Riwayat Pemeriksaan')
@section('subjudul', 'Seluruh warga. Filter kategori, klik Lihat Riwayat untuk detail per kunjungan.')

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('riwayat.index') }}" class="saring" role="search">
        <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama / NIK warga..." aria-label="Cari nama atau NIK warga">
        <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()">
            <option value="">Filter Kategori (semua)</option>
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
                    <th scope="col">Nama</th>
                    <th scope="col">NIK</th>
                    <th scope="col">Kategori Usia</th>
                    <th scope="col">Kunjungan Terakhir</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarWarga as $warga)
                    <tr>
                        <td>{{ $warga->nama_warga }}</td>
                        <td>{{ $warga->nik }}</td>
                        <td>@include('partials.badge-warga', ['warga' => $warga])</td>
                        <td>{{ $warga->kunjungan_terakhir ? \Illuminate\Support\Carbon::parse($warga->kunjungan_terakhir)->format('d/m/Y') : '-' }}</td>
                        <td><a href="{{ route('riwayat.warga', $warga) }}" class="tombol kecil">Lihat Riwayat</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="kosong">
                            @if ($cari !== '' || $kategoriDipilih)
                                Tidak ada warga yang cocok dengan pencarian atau filter.
                            @else
                                Belum ada data warga.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftarWarga, 'satuan' => 'warga'])

    <div class="info">Seluruh warga terekam di sini. Setiap pemeriksaan yang sudah di-Submit, serta setiap tambahan centang vaksin/ASI balita per kunjungan, tercatat sebagai riwayat. Filter kategori mempersempit daftar. Klik Lihat Riwayat untuk membuka detail per tanggal.</div>
@endsection
