<x-layouts.app title="Buat Kegiatan Baru" active="kegiatan">

    <div class="mx-auto max-w-2xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('kegiatan.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>

        <h2 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">Buat Kegiatan Baru</h2>
        <p class="mt-1 text-sm text-slate-500">
            Jadwalkan pelaksanaan Posyandu. Kegiatan langsung berjalan setelah dibuat.
        </p>


        {{-- ═══════════ ADA KEGIATAN AKTIF ═══════════ --}}
        @if ($kegiatanAktif)
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <p class="text-sm font-medium text-amber-900">
                    Masih ada kegiatan yang berlangsung:
                    <strong>{{ $kegiatanAktif->nama_kegiatan }}</strong>.
                    Selesaikan kegiatan tersebut terlebih dahulu melalui halaman
                    <a href="{{ route('presensi.index', $kegiatanAktif) }}"
                       class="font-bold underline underline-offset-2">Presensi</a>.
                </p>
            </div>
        @endif


        {{-- ═══════════ ERROR VALIDASI ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    Kegiatan belum bisa dibuat. Periksa kembali isian berikut:
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        {{-- ═══════════ FORM ═══════════ --}}
        <form method="POST" action="{{ route('kegiatan.store') }}" class="mt-6" novalidate>
            @csrf

            <div class="card p-6">

                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Detail Kegiatan</h3>

                {{-- Nama --}}
                <div class="mt-5">
                    <label for="nama_kegiatan" class="label">Nama Kegiatan <span class="text-rose-500">*</span></label>
                    <input id="nama_kegiatan" name="nama_kegiatan" type="text" required maxlength="100"
                           value="{{ old('nama_kegiatan') }}"
                           placeholder="mis. Posyandu {{ now()->locale('id')->translatedFormat('F Y') }}"
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
                           value="{{ old('tanggal_kegiatan', now()->toDateString()) }}"
                           class="input py-2.5 @error('tanggal_kegiatan') !ring-rose-400 @enderror" />
                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Tidak boleh melewati hari ini.
                    </p>
                    @error('tanggal_kegiatan')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>


            {{-- ═══════════ TOMBOL ═══════════ --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary py-2.5" @disabled($kegiatanAktif)>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l7.028 4.347a1.307 1.307 0 010 1.972l-7.028 4.347A1.307 1.307 0 01318.347V5.653z" />
                    </svg>
                    Buat &amp; Mulai Kegiatan
                </button>

                <a href="{{ route('kegiatan.index') }}" class="btn btn-secondary py-2.5">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>