@props(['active' => ''])

@php
    // Ikon = path data Heroicons (outline, viewBox 24)
    $menu = [
        ['key' => 'dashboard',   'label' => 'Dashboard',        'path' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
        ['key' => 'kegiatan',    'label' => 'Kelola Kegiatan',  'path' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['key' => 'warga',       'label' => 'Data Warga',       'path' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-6-1.535a11.954 11.954 0 01-.037-.666A11.944 11.944 0 0112 18c2.7 0 5.258 1.03 7.22 2.687M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a11.953 11.953 0 017.38 0M15 7.5a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
        ['key' => 'pemeriksaan', 'label' => 'Pemeriksaan',      'path' => 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z'],
        ['key' => 'vaksinasi',   'label' => 'Vaksinasi Balita', 'path' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
        ['key' => 'vaksin',      'label' => 'Master Vaksin',     'path' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
        ['key' => 'riwayat',     'label' => 'Riwayat Pemeriksaan','path' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['key' => 'rekap',       'label' => 'Rekapitulasi',     'path' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        ['key' => 'pengguna',    'label' => 'Manajemen Pengguna','path' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-72 shrink-0 border-r border-slate-200
 bg-white transition-transform duration-200
        lg:static lg:translate-x-0"
       :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'">

    {{-- Header logo --}}
    <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
        <div class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-600 text-white">
            <img src="{{ asset('images/logoposyandu.png') }}"
             alt="Logo Posyandu Mawar 1"
             class="h-8 w-auto">
        </div>
        <div class="leading-tight">
            <p class="text-sm font-bold text-slate-800">Posyandu Mawar 1</p>
            <p class="text-xs text-slate-400">Tegalyoso, Sleman</p>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <p class="px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            Menu Utama
        </p>

        @foreach ($menu as $item)
            @php $isActive = $active === $item['key']; @endphp

            <a href="#"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                   'bg-emerald-50 text-emerald-700'      => $isActive,
                   'text-slate-600 hover:bg-slate-100'    => ! $isActive,
               ])
               @if($isActive) aria-current="page" @endif>

                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                     stroke-width="{{ $isActive ? '2' : '1.6' }}" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['path'] }}" />
                </svg>

<span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Logout --}}
    <div class="border-t border-slate-200 p-3">
        <button type="button" @click="$dispatch('konfirmasi-hapus', {
                    judul: 'Keluar dari Sistem?',
                    pesan: 'Anda akan keluar dari sesi ini.',
                    aksi: 'Ya, Keluar'
                })"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            <span>Keluar</span>
        </button>
    </div>
</aside>