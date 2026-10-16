{{-- Kelola Vaksinasi Balita (Figma 192:72 "Daftar Vaksinasi Balita"; Activity Diagram 2.19). --}}
@extends('layouts.app')

@section('judul', 'Kelola Vaksinasi Balita')
@section('subjudul', 'Daftar balita (otomatis kategori Balita). Klik Buka untuk mengisi vaksinasi & ASI')
@section('aksi-kepala')
    <span class="badge">Kegiatan aktif: {{ $kegiatan->nama_kegiatan }}</span>
@endsection

@section('isi')
    <div id="antrean-balita"
         data-url-status="{{ route('vaksinasi.status') }}"
         data-id-kegiatan="{{ $kegiatan->id_kegiatan }}"
         data-jeda="{{ max(2, (int) config('posyandu.presensi.polling_detik')) * 1000 }}">

        <form method="GET" action="{{ route('vaksinasi.index') }}" class="saring" role="search">
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama / NIK balita..." aria-label="Cari nama atau NIK balita">
            <button type="submit" class="tombol">Cari</button>
        </form>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">NIK</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody id="isi-balita" data-pesan-kosong="{{ $cari !== '' ? 'Tidak ada balita yang cocok dengan pencarian.' : 'Belum ada balita yang hadir. Balita muncul di sini setelah ditandai Hadir di menu Presensi.' }}">
                    @forelse ($daftar as $balita)
                        <tr>
                            <td>{{ $balita->nama_warga }}</td>
                            <td>{{ $balita->nik }}</td>
                            <td><span class="badge hadir">Hadir</span></td>
                            <td><a href="{{ route('vaksinasi.form', $balita->nik) }}" class="tombol kecil abu">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="kosong">{{ $cari !== '' ? 'Tidak ada balita yang cocok dengan pencarian.' : 'Belum ada balita yang hadir. Balita muncul di sini setelah ditandai Hadir di menu Presensi.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.halaman', ['data' => $daftar, 'satuan' => 'balita'])
        <p class="langsung" id="indikator-langsung">Diperbarui otomatis setiap {{ config('posyandu.presensi.polling_detik') }} detik</p>

        <div class="info">Halaman ini otomatis hanya menampilkan warga kategori Balita yang sudah Hadir. Kader mencari nama, lalu mencentang vaksin dari buku KIA fisik.</div>
    </div>
@endsection

@push('skrip')
    <script src="{{ asset('js/vaksinasi-daftar.js') }}"></script>
@endpush
