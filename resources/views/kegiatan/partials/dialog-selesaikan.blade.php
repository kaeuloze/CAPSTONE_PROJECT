<div x-show="selesai" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="selesai" x-transition.opacity @click="selesai = false"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    {{-- Modal --}}
    <div x-show="selesai" x-transition.scale.origin.center
         class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">

        {{-- Header --}}
        <div class="flex items-start gap-4 border-b border-slate-100 p-6">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-slate-900">Selesaikan kegiatan ini?</h3>
                <p class="mt-1 text-sm font-semibold text-mawar-800">{{ $kegiatan->nama_kegiatan }}</p>
            </div>

            <button type="button" @click="selesai = false"
 class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Isi --}}
        <div class="space-y-3 px-6 py-5">
            <p class="text-sm leading-relaxed text-slate-600">
                Warga yang <strong class="font-semibold text-slate-900">belum presensi</strong>
                akan otomatis tercatat <strong class="font-semibold text-rose-600">"Tidak Hadir"</strong>,
                dan kegiatan ini pindah ke tab <strong class="font-semibold text-slate-900">Arsip Kegiatan</strong>.
            </p>

            @if ($belumSubmit > 0)
                <p class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3
 text-[13px] leading-relaxed text-amber-900">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>
                        Masih ada <strong>{{ $belumSubmit }} pemeriksaan</strong> yang belum di-Submit.
                        Datanya tetap tersimpan dan dapat dilanjutkan dengan membuka kembali kegiatan dari Arsip.
                    </span>
                </p>
            @endif
        </div>

        {{-- Footer --}}
        <form method="POST" action="{{ route('kegiatan.selesaikan', $kegiatan) }}"
              class="flex justify-end gap-2.5 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
            @csrf
            <button type="button" @click="selesai = false" class="btn btn-secondary py-2 text-[13px]">
                Batal
            </button>
            <button type="submit" class="btn bg-amber-500 py-2 text-[13px] text-white shadow-sm hover:bg-amber-600">
                Ya, Selesaikan
            </button>
        </form>
    </div>
</div>