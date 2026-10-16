{{-- Detail Kegiatan dan Presensi (wireframe 2.42; Activity Diagram 2.7). Realtime: polling (keputusan 23). --}}
@extends('layouts.app')

@php $berlangsung = $kegiatan->status_kegiatan; @endphp

@section('judul', $kegiatan->nama_kegiatan)
@section('subjudul')
    {{ $kegiatan->tanggalLengkap() }} · Status: <span id="label-status">{{ $kegiatan->labelStatus() }}</span>
@endsection
@section('aksi-kepala')
    <span style="display:flex;gap:8px;flex-wrap:wrap">
        @if ($berlangsung)
            <button type="button" class="tombol utama" id="tombol-selesaikan" data-buka-dialog="dialog-selesaikan">Selesaikan Kegiatan</button>
        @endif
        <a href="{{ route('kegiatan.index', ['tab' => $berlangsung ? 'berlangsung' : 'arsip']) }}" class="tombol">← Kembali ke daftar</a>
    </span>
@endsection

@section('isi')
    <div id="presensi"
         data-url-status="{{ route('presensi.status', $kegiatan) }}"
         data-jeda="{{ max(2, (int) config('posyandu.presensi.polling_detik')) * 1000 }}"
         data-berlangsung="{{ $berlangsung ? '1' : '0' }}">

        <div class="pesan galat" role="alert" id="pesan-selesai" @if ($berlangsung) hidden @endif>
            @if ($berlangsung)
                Kegiatan ini baru saja diselesaikan. Presensi tidak bisa diubah lagi.
            @else
                Kegiatan ini sudah selesai. Presensi hanya dapat dilihat.
            @endif
        </div>

        <div class="ringkasan" aria-live="polite">
            <div><span>Total Warga</span><strong id="jumlah-total">{{ $ringkasan['total'] }}</strong></div>
            <div><span>Hadir</span><strong id="jumlah-hadir">{{ $ringkasan['hadir'] }}</strong></div>
            <div><span>Tidak Hadir</span><strong id="jumlah-tidak">{{ $ringkasan['tidak_hadir'] }}</strong></div>
        </div>

        <form method="GET" action="{{ route('presensi.index', $kegiatan) }}" class="saring" role="search">
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama / NIK warga..." aria-label="Cari nama atau NIK warga">
            <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()">
                <option value="">Filter Kategori (semua)</option>
                @foreach (\App\Enums\KategoriUsia::options() as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="tombol">Cari</button>
        </form>

        @if ($berlangsung)
            <div class="info" style="margin:0 0 16px">Salah pencet? Status presensi bisa di-toggle Hadir ↔ Belum selama kegiatan belum diselesaikan. Saat "Selesaikan Kegiatan" ditekan, sisa warga otomatis Tidak Hadir.</div>
        @endif

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th scope="col" style="width:56px">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Status</th>
                        @if ($berlangsung)<th scope="col" class="kolom-aksi">Aksi (toggle)</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $warga)
                        @php $hadir = (bool) $warga->hadir; @endphp
                        <tr data-nik="{{ $warga->nik }}">
                            <td>{{ $daftar->firstItem() + $loop->index }}</td>
                            <td>{{ $warga->nama_warga }}<br><small style="color:#6b7280">{{ $warga->nik }}</small></td>
                            <td><span class="badge">{{ \App\Enums\KategoriUsia::dariTanggalLahir($warga->tanggal_lahir, $kegiatan->tanggal_kegiatan)->label() }}</span></td>
                            <td>
                                <span @class(['badge', 'status-hadir', 'hadir' => $hadir, 'belum' => ! $hadir && $berlangsung, 'tidak' => ! $hadir && ! $berlangsung])>
                                    {{ $hadir ? 'Hadir' : ($berlangsung ? 'Belum' : 'Tidak Hadir') }}
                                </span>
                            </td>
                            @if ($berlangsung)
                                <td class="kolom-aksi">
                                    <form method="POST" action="{{ route('presensi.atur', [$kegiatan, $warga->nik]) }}" class="form-presensi">
                                        @csrf
                                        <input type="hidden" name="hadir" value="{{ $hadir ? '0' : '1' }}">
                                        <button type="submit" @class(['tombol', 'kecil', 'abu' => ! $hadir])>{{ $hadir ? 'Batalkan' : 'Tandai Hadir' }}</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $berlangsung ? 5 : 4 }}" class="kosong">
                            {{ ($cari !== '' || $kategoriDipilih) ? 'Tidak ada warga yang cocok dengan pencarian atau filter.' : 'Belum ada data warga.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.halaman', ['data' => $daftar, 'satuan' => 'warga'])

        @if ($berlangsung)
            <p class="langsung" id="indikator-langsung">Diperbarui otomatis setiap {{ config('posyandu.presensi.polling_detik') }} detik</p>
        @endif
        <div class="info">Setelah presensi, warga yang Hadir otomatis muncul di menu pemeriksaan (sesuai kategori usianya). Hanya yang Hadir yang bisa diperiksa.</div>
        <div class="info">Untuk Kategori yang berubah, maka informasi total warga juga ikut berubah menyesuaikan kategori yang dipilih.</div>
    </div>

    <div class="notifikasi" id="notifikasi" aria-live="assertive"></div>

    @if ($berlangsung)
        {{-- Dialog konfirmasi Selesaikan Kegiatan (revisi). --}}
        <dialog id="dialog-selesaikan" aria-labelledby="judul-selesaikan">
            <h3 id="judul-selesaikan">Selesaikan kegiatan ini?</h3>
            <p>Warga yang belum hadir akan otomatis dicatat <strong>Tidak Hadir</strong> dan presensi tidak bisa diubah lagi. Kegiatan masih bisa dibuka kembali dari Arsip.</p>
            <form method="POST" action="{{ route('kegiatan.selesaikan', $kegiatan) }}" data-memuat="Menyelesaikan...">
                @csrf
                <div class="dialog-tombol">
                    <button type="button" class="tombol" data-tutup-dialog>Tidak</button>
                    <button type="submit" class="tombol utama">Ya, Selesaikan</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection

@push('skrip')
    <script src="{{ asset('js/presensi.js') }}"></script>
@endpush
