@props(['title' => 'Dashboard'])

<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-6">

    {{-- Tombol menu mobile --}}
    <button type="button" @click="$store.sidebar.toggle()"
        aria-label="Buka menu"
        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    {{-- Judul halaman --}}
    <div class="min-w-0 flex-1">
        <h1 class="truncate text-base font-semibold text-slate-800">{{ $title }}</h1>
        <p class="text-xs text-slate-400">
            {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>

    {{-- Pencarian global --}}
    <div class="relative hidden w-72 md:block">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
        </svg>
        <input type="search" placeholder="Cari NIK atau nama warga..."
               class="input pl-9" />
    </div>

    {{-- Notifikasi --}}
    <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500"></span>
    </button>

    {{-- User --}}
    <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
        <div class="grid h-9 w-9 place-items-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
            MK
        </div>
        <div class="hidden leading-tight sm:block">
            <p class="text-sm font-medium text-slate-800">Maura A. K.</p>
            <p class="text-xs text-slate-400">Kader / Admin</p>
        </div>
    </div>
</header>