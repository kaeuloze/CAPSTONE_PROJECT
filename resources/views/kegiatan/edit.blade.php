@php
    $status = (string) old('status_kegiatan', $kegiatan->status_kegiatan ? '1' : '0');
    $backUrl = route('kegiatan.index', ['tab' => $kegiatan->status_kegiatan ? 'berlangsung' : 'arsip']);
@endphp


<x-layouts.app title="Detail Kegiatan" active="kegiatan">

    <div class="mx-auto max-w-2xl">

        {{-- ═══════════ HEADER ═══════════ --}}
        <a href="{{ $backUrl }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>

        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">Detail Kegiatan</h2>
            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $kegiatan->status_kegiatan ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
                    : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }}">
                {{ $kegiatan->labelStatus() }}
            </span>
        </div>

        <p class="mt-1.5 text-sm text-slate-500">
            Pengubahan status otomatis menyesuaikan tampilan pada daftar kegiatan.
        </p>


        {{-- ═══════════ ERROR VALIDASI ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    Perubahan belum bisa disimpan:
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        {{-- ═══════════ FORM UBAH ═══════════ --}}
        <form method="POST" action="{{ route('kegiatan.update', $kegiatan) }}" class="mt-6" novalidate>
            @csrf
            @method('PUT')

            {{-- Token versi (optimistic locking) --}}
            <input type="hidden" name="versi"
                   value="{{ old('versi', $kegiatan->updated_at?->format('Y-m-d H:i:s')) }}">

            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Detail Kegiatan</h3>

                {{-- Nama --}}
                <div class="mt-5">
                    <label for="nama_kegiatan" class="label">Nama Kegiatan <span class="text-rose-500">*</span></label>
                    <input id="nama_kegiatan" name="nama_kegiatan" type="text" required maxlength="100"
                           value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}"
                           class="input py-2.5 @error('nama_kegiatan') !ring-rose-400 @enderror" />
                    @error('nama_kegiatan')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal --}}
                <div class="mt-4">
                    <label for="tanggal_kegiatan" class="label">Tanggal Pelaksanaan <span class="text-rose-500">*</span></label>
                    <input id="tanggal_kegiatan" name="tanggal_kegiatan" type="date" required
                           max="{{ now()->toDateString() }}"
                           value="{{ old('tanggal_kegiatan', $kegiatan->tanggal_kegiatan->format('Y-m-d')) }}"
                           class="input py-2.5 @error('tanggal_kegiatan') !ring-rose-400 @enderror" />
                    @error('tanggal_kegiatan')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status (segmented control) --}}
                <div class="mt-5">
                    <span class="label">Status Kegiatan <span class="text-rose-500">*</span></span>

                    <div class="inline-flex w-full rounded-xl border border-slate-300 bg-slate-50 p-1 sm:w-auto">
                        @foreach ([
                            ['0', 'Selesai',     'text-slate-600'],
                            ['1', 'Berlangsung', 'text-emerald-700'],
                        ] as [$nilai, $label])
                            <label class="cursor-pointer">
                                <input type="radio" name="status_kegiatan" value="{{ $nilai }}"
                                       @checked($status === $nilai)
                                       class="peer sr-only">
                                <span class="block rounded-lg px-5 py-2 text-center text-[13px] font-semibold text-slate-500 transition peer-checked:bg-white
                                              peer-checked:text-slate-900 peer-checked:shadow-sm">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('status_kegiatan')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror

                    {{-- Penjelasan konsekuensi --}}
                    <p class="mt-3 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5
                              text-[12px] leading-relaxed text-slate-600">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <span>
                            @if ($kegiatan->status_kegiatan)
                                Otomatis selesai pada
                                <strong>{{ $kegiatan->batasWaktu()->locale('id')->translatedFormat('l, j F Y H:i') }} WIB</strong>.
                                Memilih <strong>"Selesai"</strong> akan mencatat warga yang belum hadir                                sebagai <strong>Tidak Hadir</strong>.
                            @else
                                Memilih <strong>"Berlangsung"</strong> akan membuka kembali kegiatan ini
                                selama <strong>{{ config('posyandu.kegiatan.durasi_jam') }} jam</strong>
                                (hanya bila tidak ada kegiatan lain yang berlangsung).
                            @endif
                        </span>
                    </p>
                </div>
            </div>


            {{-- ═══════════ TOMBOL SIMPAN ═══════════ --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Simpan Perubahan
                </button>

                <a href="{{ $backUrl }}" class="btn btn-secondary py-2.5">Batal</a>
            </div>
        </form>


        {{-- ═══════════ HAPUS (Gambar 2.80) ═══════════ --}}
        <div class="mt-8 rounded-2xl border border-rose-200 bg-rose-50/50 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-rose-800">Hapus Kegiatan</h3>
            <p class="mt-2 text-[13px] leading-relaxed text-rose-900/80">
                Kegiatan hanya bisa dihapus bila <strong>belum ada warga</strong> yang hadir,
                diperiksa, atau divaksin.
            </p>

            <button type="button"
                    @click="$dispatch('konfirmasi-hapus', {
                        judul: 'Yakin hapus kegiatan?',
                        pesan: 'Kegiatan {{ $kegiatan->nama_kegiatan }} yang sudah dihapus tidak bisa dikembalikan.',
                        aksi: 'Ya, Hapus',
                        url: '{{ route('kegiatan.destroy', $kegiatan) }}',
                        metode: 'DELETE'
                    })"
                    class="btn bg-rose-600 py-2.5 text-white shadow-sm hover:bg-rose-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916" />
                </svg>
                Hapus Kegiatan Ini
            </button>
        </div>
    


    
    </div>

</x-layouts.app>