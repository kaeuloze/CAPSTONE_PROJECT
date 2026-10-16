{{--
    Edit Pemeriksaan (Figma 253:1732). Aturan sama dengan formulir (disetujui): isian * tetap wajib,
    tab 6-Bulan/Tahunan boleh ditambah atau dikosongkan, hasil otomatis & jenis pemeriksaan dihitung ulang.
    Simpan per bagian sehingga aman bila dua kader mengedit bersamaan.
--}}
@extends('layouts.app')

@section('judul', 'Edit Pemeriksaan — '.$pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y'))
@section('subjudul', 'Koreksi hasil ukur & screening yang sudah di-Submit.')
@section('aksi-kepala')
    <a href="{{ route('riwayat.show', $pemeriksaan) }}" class="tombol" data-batal>Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">
            {{ $errors->first('submit') ? 'Perubahan belum bisa disimpan: masih ada isian bertanda * yang kosong.' : 'Perubahan belum bisa disimpan. Periksa kembali isian yang ditandai merah.' }}
        </div>
    @endif

    @include('pemeriksaan.partials.identitas', ['keterangan' => "Kegiatan: {$kegiatan->nama_kegiatan}"])

    <form method="POST" action="{{ route('riwayat.update', $pemeriksaan) }}" id="form-pemeriksaan" novalidate>
        @csrf
        @method('PUT')

        @include('pemeriksaan.partials.isi-form', ['namaTombol' => 'Tombol Simpan Perubahan'])

        <div class="aksi-pemeriksaan">
            <p class="ket" id="catatan-aksi" data-bawaan="Tanda * wajib diisi. Tombol Simpan Perubahan aktif setelah semua isian bertanda * terisi.">Tanda <span class="wajib">*</span> wajib diisi. Tombol Simpan Perubahan aktif setelah semua isian bertanda <span class="wajib">*</span> terisi.</p>
            <div class="aksi-form">
                <button type="submit" name="aksi" value="simpan" class="tombol utama" id="tombol-submit">Simpan Perubahan</button>
                <a href="{{ route('riwayat.show', $pemeriksaan) }}" class="tombol" data-batal>Batal</a>
            </div>
        </div>
    </form>

    <div class="info">Catatan: koreksi data hasil pemeriksaan yang sudah berhasil disimpan dilakukan di sini — bukan di menu Pemeriksaan.</div>

    <dialog id="dialog-batal" aria-labelledby="judul-batal">
        <h3 id="judul-batal">Tinggalkan halaman tanpa menyimpan?</h3>
        <p>Perubahan yang belum disimpan akan hilang.</p>
        <div class="dialog-tombol">
            <button type="button" class="tombol" data-tutup-dialog>Tidak</button>
            <a href="{{ route('riwayat.show', $pemeriksaan) }}" class="tombol utama">Ya, Tinggalkan</a>
        </div>
    </dialog>
@endsection

@push('skrip')
    <script src="{{ asset('js/pemeriksaan-form.js') }}"></script>
@endpush
