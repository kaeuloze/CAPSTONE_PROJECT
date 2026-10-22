@props(['title' => 'Dashboard'])

<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-6">

    {{-- Tombol menu (mobile) --}}
    <button type="button" @click="$store.sidebar.toggle()"
            aria-label="Buka menu"
            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden">
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

    {{-- Pengguna yang login --}}
    <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-mawar-100 text-sm font-bold text-mawar-700">
            {{ strtoupper(substr(auth()->user()?->nama_kader ?? 'K', 0, 1)) }}
        </div>
        <div class="hidden leading-tight sm:block">
            <p class="max-w-[12rem] truncate text-sm font-medium text-slate-800">
                {{ auth()->user()?->nama_kader ?? 'Kader' }}
            </p>
            <p class="text-xs text-slate-400">Kader Posyandu</p>
        </div>
    </div>

</header>