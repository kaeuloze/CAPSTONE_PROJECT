{{--
    Kerangka formulir pemeriksaan (Figma "Form Pemeriksaan — ..., Wajib & Simpan/Submit (hasil rev)").
    Isi tiap bagian ada di view "{$definisi->view()}.{kunci bagian}".
    Variabel: $kegiatan, $warga, $kategori, $usia, $definisi, $nilai, $versi, $sudahAdaDraft, $tabNonaktif, $peringatan.
--}}
@extends('layouts.app')

@section('judul', $definisi->judul())
@section('subjudul', 'Form menyesuaikan kategori usia (dibuka dari menu Pemeriksaan)')
@section('aksi-kepala')
    <a href="{{ route('pemeriksaan.index') }}" class="tombol" data-batal>← Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">
            {{ $errors->first('submit') ?: 'Data belum bisa disimpan. Periksa kembali isian yang ditandai merah.' }}
        </div>
    @endif

    @include('pemeriksaan.partials.identitas')

    <form method="POST" action="{{ route('pemeriksaan.simpan', $warga->nik) }}" id="form-pemeriksaan" novalidate>
        @csrf

        @include('pemeriksaan.partials.isi-form')

        <div class="aksi-pemeriksaan">
            <p><strong>{{ $definisi->keteranganAksi() ?? 'Simpan dan Submit berlaku untuk seluruh tab ('.collect($definisi->tab())->pluck('label')->implode(', ').') — semua isian dikirim sekaligus sebagai satu pemeriksaan.' }}</strong></p>
            <p class="ket" id="catatan-aksi" data-bawaan="Tanda * wajib diisi. Tombol Submit aktif setelah semua isian bertanda * terisi.">Tanda <span class="wajib">*</span> wajib diisi. Tombol Submit aktif setelah semua isian bertanda <span class="wajib">*</span> terisi.</p>
            <div class="aksi-form">
                <button type="submit" name="aksi" value="simpan" class="tombol abu">Simpan</button>
                <button type="submit" name="aksi" value="submit" class="tombol utama" id="tombol-submit">Submit</button>
                <a href="{{ route('pemeriksaan.index') }}" class="tombol" data-batal>Batal</a>
            </div>
        </div>
    </form>

    <dialog id="dialog-peringatan" aria-labelledby="judul-peringatan">
        <h3 id="judul-peringatan">Perhatian</h3>
        <p id="isi-peringatan"></p>
        <div class="dialog-tombol">
            <button type="button" class="tombol" id="peringatan-kembali">Kembali</button>
            <button type="button" class="tombol utama" id="peringatan-lanjut">Lanjutkan</button>
        </div>
    </dialog>

    <dialog id="dialog-batal" aria-labelledby="judul-batal">
        <h3 id="judul-batal">Tinggalkan form tanpa menyimpan?</h3>
        <p>Perubahan yang belum disimpan akan hilang.</p>
        <div class="dialog-tombol">
            <button type="button" class="tombol" data-tutup-dialog>Tidak</button>
            <a href="{{ route('pemeriksaan.index') }}" class="tombol utama">Ya, Tinggalkan</a>
        </div>
    </dialog>
@endsection

@push('skrip')
    <script src="{{ asset('js/pemeriksaan-form.js') }}"></script>
@endpush
