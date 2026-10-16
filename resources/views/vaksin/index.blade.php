{{-- Master Vaksin — Kelola Jenis Vaksin (Figma 192:320) + popup Tambah / Edit Vaksin (Figma 208:894). --}}
@extends('layouts.app')

@section('judul', 'Master Vaksin')
@section('subjudul', 'Kelola daftar jenis vaksin. Perubahan di sini memengaruhi checklist di semua balita.')
@section('aksi-kepala')
    <button type="button" class="tombol abu" data-vaksin-tambah>+ Tambah Vaksin</button>
@endsection

@section('isi')
    <form method="GET" action="{{ route('vaksin.index') }}" class="saring" role="search">
        <input type="search" name="cari" value="{{ $cari }}" maxlength="100" placeholder="Cari nama vaksin..." aria-label="Cari nama vaksin">
        <button type="submit" class="tombol">Cari</button>
    </form>

    <div class="bungkus-tabel">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nama Vaksin</th>
                    <th scope="col">Deskripsi (opsional)</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarVaksin as $vaksin)
                    <tr>
                        <td><strong>{{ $vaksin->nama_vaksin }}</strong></td>
                        <td>{{ $vaksin->deskripsi ?: '-' }}</td>
                        <td>
                            <div class="aksi">
                                <button type="button" class="tombol kecil" data-vaksin-edit
                                        data-aksi="{{ route('vaksin.update', $vaksin) }}" data-id="{{ $vaksin->id_vaksin }}" data-nama="{{ $vaksin->nama_vaksin }}"
                                        data-deskripsi="{{ $vaksin->deskripsi }}" data-versi="{{ $vaksin->updated_at?->format('Y-m-d H:i:s') }}">Edit</button>
                                <button type="button" class="tombol kecil bahaya" data-buka-dialog="dialog-hapus"
                                        data-aksi="{{ route('vaksin.destroy', $vaksin) }}" data-nama="{{ $vaksin->nama_vaksin }}"
                                        @if ($vaksin->vaksinasi_count > 0) disabled title="Sudah tercatat pada {{ $vaksin->vaksinasi_count }} balita" @endif>Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="kosong">{{ $cari !== '' ? 'Tidak ada vaksin yang cocok dengan pencarian.' : 'Belum ada vaksin. Klik "+ Tambah Vaksin".' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.halaman', ['data' => $daftarVaksin, 'satuan' => 'vaksin'])

    <div class="info">Setiap dosis dicatat sebagai vaksin tersendiri (mis. "Polio 1", "Polio 2"). Vaksin yang sudah tercatat pada balita tidak bisa dihapus.</div>

    {{-- Popup Tambah / Edit Vaksin (Figma 208:894). Dibuka ulang otomatis bila validasi gagal. --}}
    @php
        $modeLama = old('_mode');
        $bukaUlang = $errors->hasAny(['nama_vaksin', 'deskripsi']) && $modeLama;
        $idLama = (int) old('_id');
        $aksiEdit = $modeLama === 'edit' && $idLama > 0 ? route('vaksin.update', $idLama) : null;
    @endphp
    <dialog id="dialog-vaksin" aria-labelledby="judul-vaksin" @if ($bukaUlang) data-buka-otomatis @endif>
        <form method="POST" action="{{ $aksiEdit ?? route('vaksin.store') }}" data-memuat="Menyimpan..." novalidate
              data-aksi-tambah="{{ route('vaksin.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $aksiEdit ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_mode" value="{{ $aksiEdit ? 'edit' : 'tambah' }}">
            <input type="hidden" name="_id" value="{{ $aksiEdit ? $idLama : '' }}">
            <input type="hidden" name="versi" value="{{ old('versi') }}">
            <div class="kepala-dialog">
                <h3 id="judul-vaksin">Tambah / Edit Vaksin</h3>
                <button type="button" class="tombol kecil" data-tutup-dialog aria-label="Tutup">×</button>
            </div>
            <div @class(['isian', 'salah' => $errors->has('nama_vaksin')]) style="max-width:none">
                <label for="nama_vaksin">Nama Vaksin <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" id="nama_vaksin" name="nama_vaksin" value="{{ old('nama_vaksin') }}" maxlength="100" placeholder="mis. IPV" autocomplete="off">
                @error('nama_vaksin')<div class="galat-isian">{{ $message }}</div>@enderror
            </div>
            <div @class(['isian', 'salah' => $errors->has('deskripsi')]) style="max-width:none">
                <label for="deskripsi">Deskripsi (opsional)</label>
                <input type="text" id="deskripsi" name="deskripsi" value="{{ old('deskripsi') }}" maxlength="255" placeholder="mis. Polio suntik (Inactivated)" autocomplete="off">
                @error('deskripsi')<div class="galat-isian">{{ $message }}</div>@enderror
            </div>
            <div class="dialog-tombol">
                <button type="submit" class="tombol utama">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Konfirmasi hapus (Activity Diagram 2.24; Figma Dialog Konfirmasi Hapus). --}}
    <dialog id="dialog-hapus" aria-labelledby="judul-hapus">
        <h3 id="judul-hapus">Hapus vaksin ini?</h3>
        <p><strong data-isi-nama></strong><br>Vaksin akan hilang dari checklist semua balita.</p>
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

@push('skrip')
    <script src="{{ asset('js/vaksin.js') }}"></script>
@endpush
