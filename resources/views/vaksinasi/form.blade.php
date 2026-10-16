{{--
    Vaksinasi Balita (Figma 141:2678; proposal Gambar 2.60; Activity Diagram 2.20).
    "Progressive record": centang lama tetap tampil. Tanggal dosis diisi sesuai Buku KIA (bawaan tanggal kegiatan).
    ASI Eksklusif Bulan 0–6 (seperti SIGIZI Kesga) tampil selama usia belum lewat 6 bulan.
--}}
@extends('layouts.app')

@section('judul', 'Vaksinasi Balita')
@section('subjudul', 'Arsip imunisasi dasar balita — otomatis hanya menampilkan kategori Balita. Bukan form kosong, centang lama tetap ada.')
@section('aksi-kepala')
    <a href="{{ route('vaksinasi.index') }}" class="tombol" data-batal>Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">Data belum bisa disimpan. Periksa kembali isian yang ditandai merah.</div>
    @endif

    <section class="kartu identitas-periksa">
        <span class="avatar" aria-hidden="true"></span>
        <div>
            <strong>{{ $warga->nama_warga }}</strong>
            <small>NIK {{ $warga->nik }} · Usia {{ $usiaBulan }} bulan · Kegiatan aktif: {{ $kegiatan->nama_kegiatan }}</small>
        </div>
        <span class="badge">Balita</span>
    </section>

    <form method="POST" action="{{ route('vaksinasi.simpan', $warga->nik) }}" id="form-vaksinasi" data-memuat="Menyimpan..." novalidate
          data-tanggal-kegiatan="{{ $kegiatan->tanggal_kegiatan->toDateString() }}">
        @csrf

        <h2 class="judul-bagian">Imunisasi Dasar (per dosis)</h2>
        <div class="bungkus-tabel">
            <table class="tabel-vaksin">
                <thead>
                    <tr>
                        <th scope="col">Jenis Imunisasi (dosis)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tanggal (sesuai Buku KIA)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarVaksin as $v)
                        @php
                            $tersimpan = $vaksinasi->get($v->id_vaksin);
                            $sidik = \App\Services\VaksinasiService::sidikVaksin($tersimpan);
                            $sudah = (bool) old("vaksin.{$v->id_vaksin}.sudah", $tersimpan?->status_vaksinasi ? '1' : '0');
                            $tanggal = old("vaksin.{$v->id_vaksin}.tanggal", $tersimpan?->tanggal_vaksinasi?->toDateString());
                            $galat = $errors->first("vaksin.{$v->id_vaksin}.tanggal");
                        @endphp
                        <tr data-baris-vaksin data-sudah-tersimpan="{{ $tersimpan?->status_vaksinasi ? '1' : '0' }}">
                            <td>
                                {{ $v->nama_vaksin }}
                                @if ($v->deskripsi)<br><small class="ket">{{ $v->deskripsi }}</small>@endif
                                <input type="hidden" name="awal[vaksin][{{ $v->id_vaksin }}]" value="{{ old("awal.vaksin.{$v->id_vaksin}", $sidik) }}">
                            </td>
                            <td>
                                <label class="centang-status">
                                    <input type="hidden" name="vaksin[{{ $v->id_vaksin }}][sudah]" value="0">
                                    <input type="checkbox" name="vaksin[{{ $v->id_vaksin }}][sudah]" value="1" @checked($sudah)>
                                    <span data-label-status>{{ $sudah ? 'Sudah' : 'Belum' }}</span>
                                </label>
                            </td>
                            <td @class(['salah' => $galat])>
                                <input type="date" name="vaksin[{{ $v->id_vaksin }}][tanggal]" value="{{ $tanggal }}"
                                       min="{{ $warga->tanggal_lahir->toDateString() }}" max="{{ $kegiatan->tanggal_kegiatan->toDateString() }}"
                                       aria-label="Tanggal {{ $v->nama_vaksin }}" @disabled(! $sudah)>
                                @if ($galat)<div class="galat-isian">{{ $galat }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="kosong">Belum ada vaksin di Master Vaksin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tampilAsi)
            <h2 class="judul-bagian">ASI Eksklusif</h2>
            <div class="bungkus-tabel">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Periode ASI Eksklusif</th>
                            <th scope="col">Status (Ya / Tidak / Tidak Recall)</th>
                            <th scope="col">Tanggal dicatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bulanAsi as $bulan)
                            @php $tersimpan = $asi->get($bulan); $nilai = old("asi.{$bulan}", $tersimpan?->status_asi?->value); @endphp
                            <tr>
                                <td>Bulan {{ $bulan }}
                                    <input type="hidden" name="awal[asi][{{ $bulan }}]" value="{{ old("awal.asi.{$bulan}", \App\Services\VaksinasiService::sidikAsi($tersimpan)) }}"></td>
                                <td>
                                    <select name="asi[{{ $bulan }}]" aria-label="ASI Bulan {{ $bulan }}">
                                        <option value="">Ya / Tidak / Tidak Recall</option>
                                        @foreach (\App\Enums\StatusAsi::options() as $kode => $label)
                                            <option value="{{ $kode }}" @selected($nilai === $kode)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>{{ $tersimpan?->tanggal_dicatat?->translatedFormat('M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="info">ASI Eksklusif tidak ditampilkan karena usia balita sudah lewat 6 bulan.</div>
        @endif

        <div class="aksi-form" style="margin-top:16px">
            <button type="submit" class="tombol utama">Simpan</button>
            <a href="{{ route('vaksinasi.index') }}" class="tombol" data-batal>Batal</a>
        </div>
    </form>

    <div class="info">Centang dosis sesuai catatan di Buku KIA, lalu isi tanggal pemberiannya (bawaan: tanggal kegiatan). Hapus centang bila salah mencentang. Catatan tersimpan juga tampil di Riwayat Pemeriksaan.</div>
@endsection

@push('skrip')
    <script src="{{ asset('js/vaksinasi-form.js') }}"></script>
@endpush
