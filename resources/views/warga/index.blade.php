<<<<<<< HEAD
@php
    $ref = include resource_path('views/data/reference.php');
    $kat = collect($ref['kategori'])->map(fn($v, $k) => ['value' => $k, 'label' => $v['label']])->values();
@endphp

<x-layouts.app title="Kelola Data Warga" active="warga">

    <div class="space-y-6">

        {{-- Header aksi --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-900">Data Warga</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Badge kategori usia muncul otomatis berdasarkan tanggal lahir.
                </p>
            </div>
            <button type="button" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Warga
            </button>
        </div>

        {{-- Search + Filter (SKF-04) --}}
        <x-ui.search-filter
            placeholder="Cari nama atau NIK warga..."
            :options="$kat"
 />

        {{-- Tabel --}}
        <div class="card overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="th">No</th>
                        <th class="th">Nama</th>
                        <th class="th">NIK</th>
                        <th class="th">Tanggal Lahir</th>
                        <th class="th">Kategori Usia</th>
                        <th class="th">Status</th>
                        <th class="th text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($ref['warga'] as $i => $w)
                        @php $k = $ref['kategori'][$w['kategori']]; @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="td">{{ $i + 1 }}</td>
                            <td class="td">
                                <p class="font-medium text-slate-900">{{ $w['nama'] }}</p>
                                <p class="text-xs text-slate-400">{{ $w['jk'] === 'P' ? 'Perempuan' : 'Laki-laki' }}</p>
                            </td>
                            <td class="td font-mono text-xs">{{ $w['nik'] }}</td>
                            <td class="td">{{ \Carbon\Carbon::parse($w['tanggal_lahir'])->translatedFormat('d M Y') }}</td>
                            <td class="td">
                                <x-ui.badge :warna="$k['warna']">{{ $k['label'] }}</x-ui.badge>
                            </td>
                            <td class="td">
                                @if ($w['status_kondisi'] !== 'Normal')
                                    <x-ui.badge warna="rose">{{ $w['status_kondisi'] }}</x-ui.badge>
                                @else
                                    <span class="text-slate-400">Normal</span>
                                @endif
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-1">
                                    @foreach ([
                                        ['label' => 'Lihat', 'd' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'],
                                        ['label' => 'Edit',  'd' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z'],
                                        ['label' => 'Hapus', 'd' => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916'],
                                    ] as $btn)
                                        <button type="button"
                                                @if($btn['label'] === 'Hapus')
                                                    @click="$dispatch('konfirmasi-hapus', {
                                                        judul: 'Hapus Data Warga?',
                                                        pesan: 'Data {{ $w['nama'] }} beserta seluruh riwayat pemeriksaan akan dihapus permanen.',
                                                        aksi: 'Ya, Hapus'
                                                    })"
                                                @endif
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $btn['d'] }}" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination dummy --}}
        <div class="flex items-center justify-between text-sm text-slate-500">
            <p>Menampilkan <strong>1–{{ count($ref['warga']) }}</strong> dari <strong>{{ count($ref['warga']) }}</strong> warga</p>
            <div class="flex gap-2">
                <button class="btn btn-secondary" disabled>Sebelumnya</button>
                <button class="btn btn-secondary" disabled>Berikutnya</button>
            </div>
        </div>
    </div>

</x-layouts.app>
=======
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
>>>>>>> 5403f09bfcb927f510c17c53cc6e7644a7d10284
