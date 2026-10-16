{{-- Kelola Riwayat Pemeriksaan per warga (Figma 141:921). Variabel: $warga, $daftar, $katalog. --}}
@extends('layouts.app')

@section('judul', 'Kelola Riwayat Pemeriksaan')
@section('subjudul', 'Arsip medis, lihat riwayat per tanggal, buka detail.')
@section('aksi-kepala')
    <a href="{{ route('riwayat.index') }}" class="tombol">Kembali</a>
@endsection

@section('isi')
    <section class="kartu identitas-periksa">
        <span class="avatar" aria-hidden="true"></span>
        <div>
            <strong>{{ $warga->nama_warga }}</strong>
            <small>NIK {{ $warga->nik }} · Kategori {{ $warga->kategori_usia_terkini->label() }}</small>
        </div>
    </section>

    <p class="ket">Riwayat Pemeriksaan (klik Detail untuk rincian angka ukur &amp; screening)</p>

    <div class="bungkus-tabel">
        <table>
            <thead>
                <tr>
                    <th scope="col">Tanggal / Kegiatan</th>
                    <th scope="col">Kategori saat itu</th>
                    <th scope="col">Ringkasan</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    @php
                        $jenis = collect($p->jenis_pemeriksaan)->map->label()->implode(', ');
                        $hasil = $katalog->untuk($p->kategori_usia_saat_periksa)?->ringkasan($p) ?? [];
                    @endphp
                    <tr>
                        <td>{{ $p->tanggal_pemeriksaan->translatedFormat('j F Y') }}<br><small class="ket">{{ $p->kegiatan->nama_kegiatan }}</small></td>
                        <td><span class="badge">{{ $p->kategori_usia_saat_periksa->label() }}</span></td>
                        <td>{{ collect([$jenis])->merge($hasil)->filter()->implode(' · ') }}</td>
                        <td><a href="{{ route('riwayat.show', $p) }}" class="tombol kecil">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="kosong">Belum ada pemeriksaan yang di-Submit untuk warga ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftar, 'satuan' => 'pemeriksaan'])
@endsection
