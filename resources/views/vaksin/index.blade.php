@php
    // Bila validasi gagal, buka ulang popup dengan mode & data lama.
    $modeLama   = old('_mode');
    $bukaUlang  = $errors->hasAny(['nama_vaksin', 'deskripsi']) && $modeLama;
    $idLama     = (int) old('_id');
    $aksiEdit   = ($modeLama === 'edit' && $idLama > 0) ? route('vaksin.update', $idLama) : null;
@endphp


<x-layouts.app title="Master Vaksin" active="vaksin">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Master Vaksin</h2>
            <p class="mt-1 text-sm text-slate-500">
                Kelola daftar jenis vaksin. Perubahan di sini memengaruhi checklist
                di <strong>semua</strong> balita.
            </p>
        </div>

        {{-- HOOK [data-vaksin-tambah] --}}
        <button type="button" data-vaksin-tambah class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Vaksin
        </button>
    </div>


    {{-- ═══════════ FLASH ═══════════ --}}
    @if (session('sukses'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('sukses') }}</p>
        </div>
    @endif

    @if (session('galat'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-rose-800">{{ session('galat') }}</p>
        </div>
    @endif


    {{-- ═══════════ PENCARIAN ═══════════ --}}
    <form method="GET" action="{{ route('vaksin.index') }}"
          class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center" role="search">

        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
            </svg>
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100"
                   placeholder="Cari nama vaksin..."
                   aria-label="Cari nama vaksin"
                   class="input py-2 pl-9 text-[13px]" />
        </div>

        <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

        @if ($cari !== '')
            <a href="{{ route('vaksin.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
        @endif
    </form>


    {{-- ═══════════ TABEL ═══════════ --}}
    <div class="card overflow-hidden">

        @if ($daftarVaksin->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ $cari !== '' ? 'Vaksin tidak ditemukan' : 'Belum ada vaksin' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    {{ $cari !== '' ? 'Coba kata kunci lain.' : 'Klik "T Vaksin" untuk menambah jenis vaksin pertama.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Nama Vaksin</th>
                            <th class="th">Deskripsi (opsional)</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($daftarVaksin as $vaksin)
                            <tr class="transition hover:bg-mawar-50/40">
                                <td class="td text-slate-400">{{ $daftarVaksin->firstItem() + $loop->index }}</td>

                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-mawar-50 text-mawar-700 ring-1 ring-mawar-200/70">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5" />
                                            </svg>
                                        </span>
                                        <span class="font-semibold text-slate-900">{{ $vaksin->nama_vaksin }}</span>
                                    </div>
                                </td>

                                <td class="td text-[13px] text-slate-600">
                                    {{ $vaksin->deskripsi ?: '—' }}
                                </td>

                                <td class="td">
                                    <div class="flex justify-end gap-1.5">

                                        {{-- HOOK [data-vaksin-edit] --}}
                                        <button type="button" data-vaksin-edit
                                                data-aksi="{{ route('vaksin.update', $vaksin) }}"
                                                data-id="{{ $vaksin->id_vaksin }}"
                                                data-nama="{{ $vaksin->nama_vaksin }}"
                                                data-deskripsi="{{ $vaksin->deskripsi }}"
                                                data-versi="{{ $vaksin->updated_at?->format('Y-m-d H:i:s') }}"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px]
 font-bold text-slate-700 transition hover:border-mawar-300 hover:bg-mawar-50 hover:text-mawar-800">
                                            Edit
                                        </button>

                                        <button type="button"
                                                @click="$dispatch('konfirmasi-hapus', {
    judul: 'Yakin hapus vaksin?',
    pesan: @js($vaksin->nama_vaksin.' akan hilang dari checklist semua balita.'),
    aksi: 'Ya, Hapus',
    url: @js(route('vaksin.destroy', $vaksin))
})"
                                                @disabled($vaksin->vaksinasi_count > 0)
                                                title="{{ $vaksin->vaksinasi_count > 0 ? 'Sudah tercatat pada ' . $vaksin->vaksinasi_count . ' balita' : 'Hapus vaksin' }}"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px]
   font-bold text-slate-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-slate-700">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($daftarVaksin->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                    <p class="text-[12px] text-slate-500">
                        Menampilkan <strong class="font-bold text-slate-700">{{ $daftarVaksin->firstItem() }}–{{ $daftarVaksin->lastItem() }}</strong>
                        dari <strong class="font-bold text-slate-700">{{ $daftarVaksin->total() }}</strong> vaksin
                    </p>
                    <div>{{ $daftarVaksin->links() }}</div>
                </div>
            @endif
        @endif
    </div>


    {{-- ═══════════ INFO ═══════════ --}}
    <p class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
 text-[12px] leading-relaxed text-slate-500">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <span>
            Setiap dosis dicatat sebagai vaksin tersendiri (mis. <strong>"Polio 1"</strong>,
            <strong>"Polio 2"</strong>). Vaksin yang sudah tercatat pada balita
            <strong>tidak bisa dihapus</strong>.
        </span>
    </p>


    {{-- ══════════════════════════════════════════════════════
         POPUP TAMBAH / EDIT VAKSIN  (Gambar 2.69)
         ══════════════════════════════════════════════════════ --}}
    <dialog id="dialog-vaksin" aria-labelledby="judul-vaksin"
            @if ($bukaUlang) data-buka-otomatis @endif
            class="w-[calc(100vw-2rem)] max-w-md rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">

        <form method="POST"
              action="{{ $aksiEdit ?? route('vaksin.store') }}"
              data-aksi-tambah="{{ route('vaksin.store') }}"
              novalidate>

            @csrf

            {{-- Field yang dikendalikan JS --}}
            <input type="hidden" name="_method" value="{{ $aksiEdit ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_mode" value="{{ $aksiEdit ? 'edit' : 'tambah' }}">
            <input type="hidden" name="_id" value="{{ $aksiEdit ? $idLama : '' }}">
            <input type="hidden" name="versi" value="{{ old('versi') }}">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                <h3 id="judul-vaksin" class="text-base font-semibold text-slate-900">Tambah / Edit Vaksin</h3>
                <button type="button" onclick="this.closest('dialog').close()"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Isi --}}
            <div class="space-y-4 px-6 py-5">

                <div @class(['salah' => $errors->has('nama_vaksin')])>
                    <label for="nama_vaksin" class="label">
                        Nama Vaksin <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nama_vaksin" name="nama_vaksin" maxlength="100"
                           autocomplete="off" placeholder="mis. IPV"
                           class="input py-2.5 @error('nama_vaksin') !ring-rose-400 @enderror" />

                    @error('nama_vaksin')
                        <p class="galat-isian mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div @class(['salah' => $errors->has('deskripsi')])>
                    <label for="deskripsi" class="label">
                        Deskripsi <span class="text-xs font-normal text-slate-400">(opsional)</span>
                    </label>
                    <input type="text" id="deskripsi" name="deskripsi" maxlength="255"
                           autocomplete="off" placeholder="mis. Polio suntik (Inactivated)"
                           class="input py-2.5 @error('deskripsi') !ring-rose-400 @enderror" />

                    @error('deskripsi')
                        <p class="galat-isian mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2.5 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-secondary py-2 text-[13px]">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary py-2 text-[13px]">Simpan</button>
            </div>
        </form>
    </dialog>


    {{-- ═══════════ JS ═══════════ --}}
    <script src="{{ asset('js/vaksin.js') }}" defer></script>

</x-layouts.app>