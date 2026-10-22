{{--
    Dialog konfirmasi serbaguna.
    WAJIB dipakai sebagai WRAPPER di layouts/app.blade.php (membungkus isi halaman),
    BUKAN ditaruh di akhir body atau di dalam halaman.
    Alasan: Alpine $dispatch hanya merambat ke ANCESTOR, bukan sibling.

    Cara pakai (trigger dari halaman mana pun):
      $dispatch('konfirmasi-hapus', {
          judul: 'Judul dialog',
          pesan: 'Keterangan tambahan.',
          aksi:  'Ya, Hapus', // teks tombol konfirmasi
          tone:  'danger'                // danger | warning | success
      })
--}}
<div x-data="{ open: false, judul: '', pesan: '', aksi: 'Ya, Lanjutkan', tone: 'danger' }"
     @konfirmasi-hapus.window="open = true; judul = $event.detail.judul; pesan = $event.detail.pesan; aksi = $event.detail.aksi ?? 'Ya, Lanjutkan'; tone = $event.detail.tone ?? 'danger'"
     @keydown.escape.window="open = false">

    {{-- ============ ISI HALAMAN (slot) ============ --}}
    {{ $slot }}

    {{-- ============ BACKDROP ============ --}}
    <div x-show="open" x-transition.opacity
         @click="open = false"
         class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm"></div>

    {{-- ============ MODAL ============ --}}
    <div x-show="open" x-cloak x-transition.scale
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true">

        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">

            <div class="flex items-start gap-4">

                {{-- Ikon --}}
                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full"
                     :class="{
                        'bg-rose-100 text-rose-600'       : tone === 'danger',
                        'bg-amber-100 text-amber-600'     : tone === 'warning',
                        'bg-emerald-100 text-emerald-600' : tone === 'success',
                     }">
                    <svg x-show="tone !== 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <svg x-show="tone === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                {{-- Teks --}}
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-slate-900" x-text="judul"></h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500" x-text="pesan"></p>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="btn btn-secondary">
                    Batal
                </button>
                <button type="button" @click="open = false" class="btn"
                        :class="{
                            'bg-rose-600 text-white hover:bg-rose-700'        : tone === 'danger',
                            'bg-amber-500 text-white hover:bg-amber-600'      : tone === 'warning',
                            'bg-emerald-600 text-white hover:bg-emerald-700' : tone === 'success',
                        }"
                        x-text="aksi"></button>
            </div>

        </div>
    </div>
</div>