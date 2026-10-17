<<<<<<< HEAD
<x-layouts.app title="Kelola Kegiatan" active="kegiatan">

    <div class="space-y-6">

        {{-- Tab --}}
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6">
                <a href="#" class="border-b-2 border-emerald-600 px-1 pb-3 text-sm font-semibold text-emerald-600">
                    Kegiatan Berlangsung
                </a>
                <a href="#" class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 hover:border-slate-300">
                    Arsip Kegiatan
                </a>
            </nav>
        </div>

        {{-- EMPTY STATE (Gambar 2.42) --}}
        <x-empty-state
            icon="calendar"
            judul="Belum ada kegiatan berlangsung"
            deskripsi="Belum ada kegiatan Posyandu yang sedang berjalan saat ini. Buat kegiatan baru untuk memulai pencatatan kehadiran warga."
            :cta="['label' => 'Buat Kegiatan Baru', 'url' => '#']"
        />

    </div>
</x-layouts.app>
=======
{{-- Kelola Kegiatan (Figma "Daftar Kegiatan — hasil rev": Tab Berlangsung, Tab Arsip, Tampilan kosong). --}}
@extends('layouts.app')

@section('judul', 'Kelola Kegiatan')
@section('subjudul', 'Setiap pelaksanaan posyandu dicatat sebagai satu kegiatan (event)')
@section('aksi-kepala')
    <a href="{{ route('kegiatan.create') }}" class="tombol abu">+ Buat Kegiatan Baru</a>
@endsection

@section('isi')
    <nav class="tab" aria-label="Status kegiatan">
        <a href="{{ route('kegiatan.index') }}" @class(['aktif' => $tab === 'berlangsung']) @if ($tab === 'berlangsung') aria-current="page" @endif>Kegiatan Berlangsung</a>
        <a href="{{ route('kegiatan.index', ['tab' => 'arsip']) }}" @class(['aktif' => $tab === 'arsip']) @if ($tab === 'arsip') aria-current="page" @endif>
            Arsip Kegiatan <span class="badge kecil">{{ $jumlahArsip }}</span>
        </a>
    </nav>

    @if ($tab === 'berlangsung')
        @if ($aktif)
            <article class="kartu-kegiatan berlangsung">
                <div class="kepala-kartu">
                    <div class="judul-kartu">
                        <span class="ikon" aria-hidden="true"></span>
                        <div>
                            <h2><a href="{{ route('kegiatan.edit', $aktif) }}">{{ $aktif->nama_kegiatan }}</a></h2>
                            <p class="meta">{{ $aktif->tanggalLengkap() }}</p>
                        </div>
                    </div>
                    <div class="tombol-kartu">
                        <a href="{{ route('presensi.index', $aktif) }}" class="tombol">Presensi</a>
                        <button type="button" class="tombol" data-buka-dialog="dialog-selesaikan">Selesaikan Kegiatan</button>
                    </div>
                </div>

                <div class="ringkasan">
                    <div><span>Total Warga</span><strong>{{ $ringkasan['total'] }}</strong></div>
                    <div><span>Hadir</span><strong>{{ $ringkasan['hadir'] }}</strong></div>
                    <div><span>Belum Hadir</span><strong>{{ $ringkasan['tidak_hadir'] }}</strong></div>
                </div>

                <p class="label">Kehadiran per kategori (Hadir / Total)</p>
                <div class="per-kelompok">
                    @foreach ($perKelompok as $kelompok)
                        <div><span>{{ $kelompok['label'] }}</span><strong>{{ $kelompok['hadir'] }} / {{ $kelompok['total'] }}</strong></div>
                    @endforeach
                </div>

                <p class="petunjuk">*Klik kartu untuk melihat dan mengubah detail kegiatan</p>
            </article>

            @include('kegiatan.partials.dialog-selesaikan', ['kegiatan' => $aktif, 'belumSubmit' => $belumSubmit])
        @else
            <div class="kosong-besar">
                <span class="ikon-bulat" aria-hidden="true"></span>
                <h2>Belum ada kegiatan yang sedang berlangsung</h2>
                <p>Buat kegiatan posyandu bulan ini untuk mulai mencatat presensi, pemeriksaan, dan vaksinasi balita. Kegiatan sebelumnya yang sudah selesai tetap tersimpan di tab Arsip Kegiatan.</p>
                <div class="aksi-form" style="justify-content:center">
                    <a href="{{ route('kegiatan.create') }}" class="tombol abu">+ Buat Kegiatan Baru</a>
                    <a href="{{ route('kegiatan.index', ['tab' => 'arsip']) }}" class="tombol">Lihat Arsip Kegiatan</a>
                </div>
            </div>
        @endif

        <div class="info">Tab ini menampilkan kegiatan yang sedang berlangsung pada hari pelaksanaan posyandu. Kegiatan yang diselesaikan, atau aktif lebih dari {{ config('posyandu.kegiatan.durasi_jam') }} jam, otomatis pindah ke tab Arsip Kegiatan. Hitungan {{ config('posyandu.kegiatan.durasi_jam') }} jam dimulai sejak kegiatan dibuat, atau sejak dibuka kembali dari arsip.</div>
    @else
        <form method="GET" action="{{ route('kegiatan.index') }}" class="saring" role="search">
            <input type="hidden" name="tab" value="arsip">
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama kegiatan..." aria-label="Cari nama kegiatan">
            <select name="tahun" aria-label="Filter tahun" onchange="this.form.submit()">
                <option value="">Tahun: Semua</option>
                @foreach ($daftarTahun as $tahun)
                    <option value="{{ $tahun }}" @selected((int) $tahunDipilih === (int) $tahun)>Tahun: {{ $tahun }}</option>
                @endforeach
            </select>
            <button type="submit" class="tombol">Cari</button>
        </form>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th scope="col" style="width:56px">No</th>
                        <th scope="col">Nama Kegiatan</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Hadir / Total</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $kegiatan)
                        <tr>
                            <td>{{ $daftar->firstItem() + $loop->index }}</td>
                            <td>{{ $kegiatan->nama_kegiatan }}</td>
                            <td>{{ $kegiatan->tanggalLengkap() }}</td>
                            <td>{{ $kegiatan->jumlah_hadir }} / {{ $kegiatan->jumlah_tercatat }}</td>
                            <td>
                                <div class="aksi">
                                    <a href="{{ route('presensi.index', $kegiatan) }}" class="tombol kecil">Lihat Presensi</a>
                                    <a href="{{ route('kegiatan.edit', $kegiatan) }}" class="tombol kecil">✎ Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kosong">
                            {{ ($cari !== '' || $tahunDipilih) ? 'Tidak ada kegiatan yang cocok dengan pencarian atau filter.' : 'Belum ada kegiatan yang selesai.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.halaman', ['data' => $daftar, 'satuan' => 'kegiatan'])

        <div class="info">Arsip berisi kegiatan yang sudah selesai. "Lihat Presensi" membuka daftar kehadiran kegiatan tersebut (hanya-lihat). "✎ Edit" membuka halaman Detail dan Edit Kegiatan; bila perlu koreksi presensi, ubah status menjadi "Berlangsung" di sana, lalu kegiatan kembali muncul di tab Kegiatan Berlangsung.</div>
    @endif
@endsection
>>>>>>> 5403f09bfcb927f510c17c53cc6e7644a7d10284
