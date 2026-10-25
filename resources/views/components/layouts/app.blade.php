{{--
    ==========================================================
    LAYOUT UTAMA ADMIN ·  Posyandu Mawar 1
    Capstone Project 2 - Maura Anindita Kirana (Frontend)
    ==========================================================
    Pakai:  <x-layouts.app title="..." active="menu-key"> ... </x-layouts.app>

    @param string|null $title   Judul halaman (dipakai di <title> & navbar)
    @param string|null $active  Key menu sidebar yg aktif (dashboard/kegiatan/warga/...)
    ==========================================================
--}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} · Posyandu Mawar 1</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-50 text-slate-800 antialiased">

    {{-- ==========================================================
         WRAPPER DIALOG KONFIRMASI
         WAJIB membungkus seluruh isi halaman (bukan ditaruh di
         akhir body), karena Alpine $dispatch hanya merambat ke
         ANCESTOR - bukan ke sibling. Tanpa wrapper ini, tombol
         hapus/logout tidak akan memunculkan dialog.
         ========================================================== --}}
    <x-confirm-dialog>

        <div class="flex min-h-full">
            {{-- Overlay gelap saat sidebar dibuka di HP --}}
            <div x-show="$store.sidebar.open" x-cloak x-transition.opacity
                @click="$store.sidebar.close()"
                class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

            {{-- ================= SIDEBAR ================= --}}
            <x-sidebar :active="$active ?? ''" />

            {{-- ================= KONTEN ================= --}}
            <div class="flex min-w-0 flex-1 flex-col">

                <x-navbar :title="$title ?? 'Dashboard'" />

                <main class="flex-1 p-4 lg:p-6">

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
                    {{-- Kalau dipanggil dengan $slot (component), pakai slot --}}
                    @isset($slot)
                        {{ $slot }}
                    @else
                        {{-- Kalau dipanggil dengan @section, pakai yield --}}
                        @yield('content')
                    @endisset

                </main>

                <footer class="border-t border-slate-200 px-6 py-3 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Posyandu Mawar 1
                    &middot; Sistem Informasi Pengelolaan Data Terintegrasi </footer>

            </div>
            {{-- =============== /KONTEN =============== --}}

        </div>

    </x-confirm-dialog>
        {{-- Script khusus halaman (pemeriksaan-form, kalkulator, dll) --}}
    @stack('skrip')


</body>
</html>