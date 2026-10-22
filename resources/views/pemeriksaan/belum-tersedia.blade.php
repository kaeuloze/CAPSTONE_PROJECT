<x-layouts.app title="Kelola Pemeriksaan" active="pemeriksaan">

    <div class="mx-auto max-w-2xl py-8">
        <div class="card p-10 text-center">

            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </span>

            <h2 class="mt-6 text-lg font-bold tracking-tight text-slate-900">
                Belum ada kegiatan Posyandu yang aktif
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                Daftar warga untuk pemeriksaan hanya muncul jika ada kegiatan Posyandu yang sedang
                berlangsung. Saat ini tidak ada kegiatan aktif, sehingga belum ada warga
                yang bisa diperiksa.
            </p>

            <p class="mx-auto mt-5 max-w-md rounded-xl border border-mawar-100 bg-mawar-50/60 px-4 py-3
                      text-[13px] leading-relaxed text-mawar-800">
                Buka menu <strong>Kegiatan</strong> lalu buat atau mulai satu kegiatan agar
                warga yang hadir dapat diperiksa.
            </p>

            <a href="{{ route('kegiatan.index') }}" class="btn btn-primary mt-6">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Pergi ke Kegiatan
            </a>
        </div>
    </div>

</x-layouts.app>