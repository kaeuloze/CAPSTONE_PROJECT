<<<<<<< HEAD
<x-layouts.app title="Pemeriksaan" active="pemeriksaan">

    <div class="space-y-6">

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Daftar Pemeriksaan</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Hanya warga yang berstatus <strong>Hadir</strong> pada kegiatan aktif yang ditampilkan.
                    </p>
                </div>
                <x-ui.badge warna="emerald">Draft</x-ui.badge>
            </div>
        </div>

        {{-- EMPTY STATE (Gambar 2.54) --}}
        <x-empty-state
            icon="clipboard"
            judul="Belum ada kegiatan aktif"
            deskripsi="Daftar warga yang dapat diperiksa terikat pada presensi kegiatan yang sedang berlangsung. Selesaikan atau buat kegiatan terlebih dahulu."
            :cta="['label' => 'Kelola Kegiatan', 'url' => '#']"
        />

    </div>

</x-layouts.app>
=======
{{-- Kelola Pemeriksaan (Figma "Pemeriksaan — Tanpa Kolom Status (hasil rev)" 414:65). --}}
@extends('layouts.app')

@section('judul', 'Kelola Pemeriksaan')
@section('subjudul', 'Hanya warga yang sudah presensi (Hadir) yang muncul di sini')
@section('aksi-kepala')
    <span class="badge">Kegiatan aktif: {{ $kegiatan->nama_kegiatan }}</span>
@endsection

@section('isi')
    <div id="antrean"
         data-url-status="{{ route('pemeriksaan.status') }}"
         data-id-kegiatan="{{ $kegiatan->id_kegiatan }}"
         data-jeda="{{ max(2, (int) config('posyandu.presensi.polling_detik')) * 1000 }}">

        <form method="GET" action="{{ route('pemeriksaan.index') }}" class="saring" role="search">
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama / NIK warga yang sudah hadir..." aria-label="Cari nama atau NIK warga yang sudah hadir">
            <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()">
                <option value="">Filter Kategori (semua)</option>
                @foreach (\App\Enums\KategoriPemeriksaan::options() as $nilai => $label)
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
                        <th scope="col">Kategori Usia</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody id="isi-antrean" data-pesan-kosong="{{ ($cari !== '' || $kategoriDipilih) ? 'Tidak ada warga yang cocok dengan pencarian atau filter.' : 'Belum ada warga yang hadir untuk diperiksa. Warga muncul di sini setelah ditandai Hadir di menu Presensi.' }}">
                    @forelse ($daftar as $warga)
                        @php $draft = $warga->status_pemeriksaan === \App\Enums\StatusPemeriksaan::Draft->value; @endphp
                        <tr>
                            <td>{{ $daftar->firstItem() + $loop->index }}</td>
                            <td>{{ $warga->nama_warga }}</td>
                            <td>{{ $warga->nik }}</td>
                            <td><span class="badge">{{ \App\Enums\KategoriPemeriksaan::from($warga->kategori_form)->label() }}</span></td>
                            <td>
                                <a href="{{ route('pemeriksaan.form', $warga->nik) }}" @class(['tombol', 'kecil', 'abu' => ! $draft, 'draft' => $draft])>
                                    {{ $draft ? 'Pemeriksaan Belum Selesai' : 'Mulai Pemeriksaan' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kosong">
                            {{ ($cari !== '' || $kategoriDipilih) ? 'Tidak ada warga yang cocok dengan pencarian atau filter.' : 'Belum ada warga yang hadir untuk diperiksa. Warga muncul di sini setelah ditandai Hadir di menu Presensi.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.halaman', ['data' => $daftar, 'satuan' => 'warga'])
        <p class="langsung" id="indikator-langsung">Diperbarui otomatis setiap {{ config('posyandu.presensi.polling_detik') }} detik</p>

        <div class="info">Tombol "Mulai Pemeriksaan" membuka form sesuai kategori usia (deteksi otomatis dari tanggal lahir).</div>
        <div class="info">Alur input: kader mengisi data (BB, TB, dll) → klik Simpan → IMT muncul otomatis &amp; masuk grafik Dashboard. Selama belum di-Submit, data masih bisa diubah. Setelah di-Submit, koreksi dilakukan di menu Kelola Riwayat Pemeriksaan.</div>
        <div class="info">Balita tidak ada di sini. Hadir dan Vaksinasi Balita saja.</div>
    </div>
@endsection

@push('skrip')
    <script src="{{ asset('js/pemeriksaan-daftar.js') }}"></script>
@endpush
>>>>>>> 5403f09bfcb927f510c17c53cc6e7644a7d10284
