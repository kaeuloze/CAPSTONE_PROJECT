{{-- Detail Warga, hanya-lihat (wireframe Gambar 2.45; Activity Diagram 2.29). --}}
@extends('layouts.app')

@section('judul', 'Detail Warga')
@section('subjudul', 'Kategori usia terdeteksi otomatis dari tanggal lahir')
@section('aksi-kepala')
    <span style="display:flex;gap:8px">
        <a href="{{ route('warga.edit', $warga) }}" class="tombol">Edit</a>
        <a href="{{ route('warga.index') }}" class="tombol">← Kembali</a>
    </span>
@endsection

@section('isi')
    <section class="kartu">
        <h2>Identitas Warga</h2>
        <div class="kisi">
            <div><span class="label">Nama lengkap</span><div class="nilai">{{ $warga->nama_warga }}</div></div>
            <div><span class="label">NIK</span><div class="nilai">{{ $warga->nik }}</div></div>
            <div><span class="label">Tanggal lahir</span><div class="nilai">{{ $warga->tanggal_lahir->translatedFormat('d-m-Y') }}</div></div>
            <div><span class="label">Nomor HP (opsional)</span><div class="nilai">{{ $warga->no_hp ?: '-' }}</div></div>
            <div><span class="label">Jenis Kelamin</span><div class="nilai">{{ $warga->jenis_kelamin_label }}</div></div>
            <div class="penuh"><span class="label">Alamat</span><div class="nilai">{{ $warga->alamat }}</div></div>
        </div>
        <div class="info">Badge kategori usia muncul otomatis dari tanggal lahir: Balita / Remaja / Dewasa / Lansia / Ibu Hamil.</div>
        <p style="font-size:12px;color:#4b5563;margin:16px 0 0">Kategori usia (otomatis dari tgl lahir): @include('partials.badge-warga', ['warga' => $warga])</p>
    </section>

    <section class="kartu">
        <h2>Status Kondisi Khusus (manual)</h2>
        <p class="ket">Untuk ibu hamil / nifas / menyusui tidak bisa dideteksi dari tanggal lahir, jadi ditandai manual oleh kader.</p>
        <div class="kotak-status">
            <span class="nilai">Status saat ini: {{ $warga->kategori_usia_terkini->label() }}
                ({{ $warga->status_kondisi === \App\Enums\StatusKondisi::Normal ? 'tidak ada kondisi khusus' : $warga->status_kondisi->label() }})</span>
        </div>
    </section>

    <section class="kartu">
        <h2>Riwayat Penyakit Tidak Menular (PTM)</h2>
        <p class="ket">Dicatat sekali saat warga pertama kali terdaftar. Centang penyakit yang pernah/sedang dialami warga maupun yang ada pada riwayat keluarga. Data ini menjadi sumber Grafik Risiko PTM di Dashboard.</p>
        @foreach ($daftarPenyakit as $penyakit)
            <label class="centang">
                <input type="checkbox" disabled @checked(in_array($penyakit->id_penyakit, $penyakitDipilih, true))>
                {{ $penyakit->nama_penyakit }}
            </label>
        @endforeach
    </section>

    <div class="info">Kategori usia (Balita→Remaja→Dewasa→Lansia) selalu otomatis dari tanggal lahir. Status hamil/nifas/menyusui adalah penanda sementara manual.</div>
@endsection
