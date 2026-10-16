{{-- Kelola Kegiatan (wireframe 2.39; Activity Diagram 2.5; revisi: tab Berlangsung / Arsip). --}}
@extends('layouts.app')

@section('judul', 'Kelola Kegiatan')
@section('subjudul', 'Setiap pelaksanaan posyandu dicatat sebagai satu kegiatan (event)')
@section('aksi-kepala')
    <a href="{{ route('kegiatan.create') }}" class="tombol abu">+ Buat Kegiatan Baru</a>
@endsection

@section('isi')
    <nav class="tab" aria-label="Status kegiatan">
        <a href="{{ route('kegiatan.index') }}" @class(['aktif' => $tab === 'berlangsung']) @if ($tab === 'berlangsung') aria-current="page" @endif>Kegiatan Berlangsung</a>
        <a href="{{ route('kegiatan.index', ['tab' => 'arsip']) }}" @class(['aktif' => $tab === 'arsip']) @if ($tab === 'arsip') aria-current="page" @endif>Arsip Kegiatan</a>
    </nav>

    @if ($daftar->isEmpty())
        <div class="kosong-besar">
            @if ($tab === 'berlangsung')
                <strong>Belum ada kegiatan yang berlangsung.</strong><br>
                Klik "+ Buat Kegiatan Baru" untuk memulai kegiatan posyandu hari ini.
            @else
                Belum ada kegiatan yang selesai.
            @endif
        </div>
    @else
        <div class="grid-kartu">
            @foreach ($daftar as $kegiatan)
                <article @class(['kartu-kegiatan', 'berlangsung' => $kegiatan->status_kegiatan])>
                    <div class="atas">
                        <span class="ikon" aria-hidden="true"></span>
                        <span class="badge">{{ $kegiatan->labelStatus() }}</span>
                    </div>
                    <h2><a href="{{ route('kegiatan.edit', $kegiatan) }}">{{ $kegiatan->nama_kegiatan }}</a></h2>
                    <p class="meta">{{ $kegiatan->tanggalLengkap() }}</p>
                    <p class="meta">Hadir: {{ $kegiatan->jumlah_hadir }} &nbsp; Total: {{ $kegiatan->status_kegiatan ? $totalWarga : $kegiatan->jumlah_tercatat }}</p>
                    <div class="tombol-kartu">
                        @if ($kegiatan->status_kegiatan)
                            <a href="{{ route('presensi.index', $kegiatan) }}" class="tombol">Presensi</a>
                        @else
                            <a href="{{ route('presensi.index', $kegiatan) }}" class="tombol kecil">Lihat Presensi</a>
                            <a href="{{ route('kegiatan.edit', $kegiatan) }}" class="tombol kecil">Edit</a>
                        @endif
                    </div>
                    <p class="petunjuk">*Klik untuk melihat detail kegiatan</p>
                </article>
            @endforeach
        </div>
    @endif

    @include('partials.halaman', ['data' => $daftar, 'satuan' => 'kegiatan'])

    @if ($tab === 'berlangsung')
        <div class="info">Hanya satu kegiatan yang dapat berlangsung pada satu waktu. Kegiatan otomatis selesai {{ config('posyandu.kegiatan.durasi_jam') }} jam setelah dibuat atau dibuka kembali; warga yang belum hadir dicatat Tidak Hadir.</div>
    @else
        <div class="info">Kegiatan di arsip dapat dibuka kembali melalui Edit → status Berlangsung (misalnya untuk melanjutkan pemeriksaan yang masih draft).</div>
    @endif
@endsection
