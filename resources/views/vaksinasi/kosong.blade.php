<x-layouts.app title="Vaksinasi Balita" active="vaksinasi">

    <div class="mx-auto max-w-2xl py-8">
        <div class="card p-10 text-center">

            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-amber-50 text-amber-500">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </span>

            <h2 class="mt-6 text-lg font-bold tracking-tight text-slate-900">
                Belum ada kegiatan Posyandu yang aktif
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                Daftar balita untuk vaksinasi hanya muncul jika ada kegiatan Posyandu yang sedang
                berlangsung<strong>dan</strong> balita sudah ditandai Hadir.
            </p>

            <p class="mx-auto mt-5 max-w-md rounded-xl border border-mawar-100 bg-mawar-50/60 px-4 py-3 text-[13px] leading-relaxed text-mawar-800">
                Buka menu <strong>Kegiatan</strong> lalu buat atau mulai satu kegiatan.
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