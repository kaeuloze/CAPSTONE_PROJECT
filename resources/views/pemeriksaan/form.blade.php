@php
    $tabNonaktif ??= [];
    $peringatan ??= [];
    $jenisDilakukan ??= null;
    $namaTombol ??= 'Tombol Submit';
@endphp


<x-layouts.app :title="$definisi->judul()" active="pemeriksaan">

    <div class="mx-auto max-w-5xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('pemeriksaan.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar pemeriksaan
        </a>

        {{-- ═══════════ IDENTITAS WARGA ═══════════ --}}
        <div class="mt-4">
            @include('pemeriksaan.partials.identitas', ['keterangan' => "Kegiatan: {$kegiatan->nama_kegiatan}"])
        </div>


        {{-- ═══════════ ERROR VALIDASI ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    {{ $errors->first('submit') ?: 'Data belum bisa disimpan. Periksa kembali isian yang ditandai merah.' }}
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


        {{-- ═══════════ FORM ═══════════ --}}
        <form method="POST" action="{{ route('pemeriksaan.simpan', $warga->nik) }}" id="form-pemeriksaan" novalidate
              class="mt-6 space-y-5">

            @csrf

            {{-- Isi form (tab + bagian + bagian umum + view tambahan) --}}
            @include('pemeriksaan.partials.isi-form', [
                'definisi'        => $definisi,
                'nilai'           => $nilai,
                'versi'           => $versi,
                'tabNonaktif'     => $tabNonaktif,
                'peringatan'      => $peringatan,
                'hanyaBaca'       => false,
                'jenisDilakukan'  => null,
                'namaTombol'      => 'Tombol Submit',
            ])

            {{-- ═══════════ AKSI FORM ═══════════ --}}
            <div class="aksi-pemeriksaan">
                <p>
                    <strong>Simpan dan Submit berlaku untuk seluruh tab
                    ({{ collect($definisi->tab())->pluck('label')->implode(', ') }})</strong> —
                    semua isian dikirim sekaligus sebagai satu pemeriksaan.
                </p>
                <p class="ket mt-1" id="catatan-aksi"
                   data-bawaan="Tanda * wajib diisi. Tombol Submit aktif setelah semua isian bertanda * terisi.">
                    Tanda <span class="wajib">*</span> wajib diisi. Tombol Submit aktif setelah
                    semua isian bertanda <span class="wajib">*</span> terisi.
                </p>

                <div class="aksi-form">
                    <button type="submit" name="aksi" value="simpan" class="btn btn-secondary py-2 text-[13px]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Simpan
                    </button>

                    <button type="submit" name="aksi" value="submit" id="tombol-submit" class="btn btn-primary py-2 text-[13px]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                        Submit
                    </button>

                    <a href="{{ route('pemeriksaan.index') }}" class="btn btn-secondary py-2 text-[13px]" data-batal>
                        Batal
                    </a>
                </div>
            </div>
        </form>


        {{-- ═══════════ DIALOG PERINGATAN INTERVAL ═══════════ --}}
        <dialog id="dialog-peringatan" aria-labelledby="judul-peringatan">
            <h3 id="judul-peringatan">Perhatian</h3>
            <p id="isi-peringatan"></p>
            <div class="dialog-tombol">
                <button type="button" class="tombol" onclick="this.closest('dialog').close()" id="peringatan-kembali">Kembali</button>
                <button type="button" class="tombol utama" id="peringatan-lanjut">Lanjutkan</button>
            </div>
        </dialog>


        {{-- ═══════════ DIALOG BATAL ═══════════ --}}
        <dialog id="dialog-batal" aria-labelledby="judul-batal">
            <h3 id="judul-batal">Tinggalkan form tanpa menyimpan?</h3>
            <p>Perubahan yang belum disimpan akan hilang.</p>
            <div class="dialog-tombol">
                <button type="button" class="tombol" onclick="this.closest('dialog').close()">Tidak</button>
                <a href="{{ route('pemeriksaan.index') }}" class="tombol utama">Ya, Tinggalkan</a>
            </div>
        </dialog>
    </div>


    {{-- ═══════════ JS ═══════════ --}}
    <script src="{{ asset('js/pemeriksaan-form.js') }}" defer></script>

</x-layouts.app>