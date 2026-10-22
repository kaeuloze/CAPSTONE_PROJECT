@php
    $tabNonaktif ??= [];
    $jenisDilakukan ??= null;
    $namaTombol = 'Tombol Simpan Perubahan';
    $peringatan = [];
@endphp


<x-layouts.app :title="'Edit Pemeriksaan — ' . $pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y')" active="riwayat">

    <div class="mx-auto max-w-5xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('riwayat.show', $pemeriksaan) }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke detail
        </a>

        <h2 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">
            Edit Pemeriksaan
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Koreksi hasil ukur &amp; screening yang sudah di-Submit. Simpan per bagian.
        </p>


        {{-- ═══════════ ERROR ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    {{ $errors->first('submit') ? 'Perubahan belum bisa disimpan: masih ada isian bertanda * yang kosong.' : 'Perubahan belum bisa disimpan. Periksa kembali isian yang ditandai merah.' }}
                    @if ($errors->any() && ! $errors->first('submit'))
                        <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif


        {{-- ═══════════ IDENTITAS ═══════════ --}}
        <div class="mt-4">
            @include('pemeriksaan.partials.identitas', [
                'keterangan' => "Kegiatan: {$kegiatan->nama_kegiatan}",
            ])
        </div>


        {{-- ═══════════ FORM ═══════════ --}}
        <form method="POST" action="{{ route('riwayat.update', $pemeriksaan) }}" id="form-pemeriksaan" novalidate
              class="mt-6 space-y-5">

            @csrf
            @method('PUT')

            @include('pemeriksaan.partials.isi-form', [
                'definisi'        => $definisi,
                'nilai'           => $nilai,
                'versi'           => $versi,
                'tabNonaktif'     => $tabNonaktif,
                'peringatan'      => [],
                'hanyaBaca'       => false,
                'jenisDilakukan'  => $jenisDilakukan,
                'namaTombol'      => 'Tombol Simpan Perubahan',
            ])

            {{-- Aksi form --}}
            <div class="aksi-pemeriksaan">
                <p><strong>Tanda * wajib diisi. Tombol Simpan Perubahan aktif setelah semua isian bertanda * terisi.</strong></p>
                <div class="aksi-form">
                    <button type="submit" name="aksi" value="simpan" class="btn btn-primary py-2 text-[13px]" id="tombol-submit">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Simpan Perubahan
                    </button>

                    <a href="{{ route('riwayat.show', $pemeriksaan) }}" class="btn btn-secondary py-2 text-[13px]" data-batal>
                        Batal
                    </a>
                </div>
            </div>
        </form>


        {{-- ═══════════ DIALOG BATAL ═══════════ --}}
        <dialog id="dialog-batal" aria-labelledby="judul-batal">
            <h3 id="judul-batal">Tinggalkan halaman tanpa menyimpan?</h3>
            <p>Perubahan yang belum disimpan akan hilang.</p>
            <div class="dialog-tombol">
                <button type="button" class="tombol" onclick="this.closest('dialog').close()">Tidak</button>
                <a href="{{ route('riwayat.show', $pemeriksaan) }}" class="tombol utama">Ya, Tinggalkan</a>
            </div>
        </dialog>
    </div>


    {{-- ═══════════ JS ═══════════ --}}
    <script src="{{ asset('js/pemeriksaan-form.js') }}" defer></script>

</x-layouts.app>