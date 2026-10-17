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

</body>
</html>