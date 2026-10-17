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
                    <tr><td colspan="4" class="kosong">Belum ada pemeriksaan yang di-Submit untuk warga ini.@if ($riwayatVaksin !== []) Catatan vaksinasi &amp; ASI ada di tabel bawah.@endif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftar, 'satuan' => 'pemeriksaan'])

    @if ($riwayatVaksin !== [])
        <p class="ket" style="margin-top:20px">Riwayat Vaksinasi &amp; ASI Eksklusif (dicatat di menu Vaksinasi Balita)</p>
        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Tanggal / Kegiatan</th>
                        <th scope="col">Kategori saat itu</th>
                        <th scope="col">Ringkasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayatVaksin as $catatan)
                        <tr>
                            <td>{{ $catatan['tanggal']?->translatedFormat('j F Y') ?? '-' }}@if ($catatan['kegiatan'])<br><small class="ket">{{ $catatan['kegiatan'] }}</small>@endif</td>
                            <td><span class="badge">Balita</span></td>
                            <td>{{ collect([
                                $catatan['vaksin'] ? 'Vaksinasi: '.implode(', ', $catatan['vaksin']) : null,
                                $catatan['asi'] ? 'ASI '.implode(', ', $catatan['asi']) : null,
                            ])->filter()->implode(' · ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="info">Koreksi catatan vaksinasi dan ASI dilakukan di menu Vaksinasi Balita saat balita hadir di kegiatan posyandu.</div>
    @endif
@endsection
