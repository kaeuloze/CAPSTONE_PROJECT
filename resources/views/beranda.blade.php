{{--
    ==========================================================
    BERANDA / HALAMAN PUBLIK  ·  SKF-01
    Posyandu Mawar 1 - Capstone Project 2 (Frontend)
    --------------------------------------------------------------
    DESAIN  : dari landing/index.blade.php (frontend)
    DATA    : dari BerandaController@index (backend)
      $profil (config posyandu.profil) · $periode (Periode)
      $filterImt · $statistik['grafik'] + ['ada_kegiatan']
      $daftarTahun · $kegiatanTerakhir
    ==========================================================
--}}

@php
    // Kategori sasaran (dari enum backend)
    $kategori = [...array_values(\App\Enums\KategoriUsia::options()), 'Ibu Hamil'];
@endphp


<x-layouts.public title="{{ $profil['nama'] }}">

{{-- ══════════════════ HEADER (PILL MELAYANG) ══════════════════ --}}
<header class="pointer-events-none sticky top-0 z-50 px-3 pt-3 sm:px-6 sm:pt-4">
    <div class="pointer-events-auto mx-auto flex h-14 max-w-5xl items-center justify-between rounded-full border border-mawar-200/70 bg-white/80 pl-2.5 pr-2 shadow-lg shadow-mawar-900/10 backdrop-blur-xl">

        {{-- Logo --}}
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logoposyandu.png') }}"
                 alt="Logo {{ $profil['nama'] }}"
                 class="h-9 w-9 rounded-full bg-white object-contain p-1 ring-1 ring-mawar-100">
            <span class="text-[15px] font-bold tracking-tight text-mawar-900">
                Posyandu <span class="text-mawar-600">{{ $profil['nama_tanpa_awalan'] }}</span>
            </span>
        </a>

        {{-- Navigasi --}}
        <nav class="flex items-center gap-0.5">
            <a href="#profil" class="hidden rounded-full px-4 py-2 text-sm font-medium text-mawar-700 transition hover:bg-mawar-50 hover:text-mawar-900 sm:inline-block">Profil</a>
            <a href="#jadwal" class="hidden rounded-full px-4 py-2 text-sm font-medium text-mawar-700 transition hover:bg-mawar-50 hover:text-mawar-900 sm:inline-block">Jadwal</a>

            <a href="{{ route('login') }}"
               class="ml-2 rounded-full bg-mawar-800 px-5 py-2 text-sm font-bold text-white transition hover:bg-mawar-700">
                Masuk
            </a>
        </nav>
    </div>
</header>

{{-- ══════════════════ HERO ══════════════════ --}}
<section class="relative -mt-[68px] sm:-mt-[72px] overflow-hidden bg-gradient-to-b from-mawar-100 via-mawar-50 to-white pb-16 pt-28 sm:pb-20 sm:pt-32">

    {{-- Dekorasi --}}
    <div class="pointer-events-none absolute inset-0 opacity-60"
         style="background-image: radial-gradient(circle at 1px 1px, rgb(8 51 68 / .12) 1px, transparent 0);
                -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 40%, transparent 100%);
                mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 40%, transparent 100%);"></div>
    <div class="pointer-events-none absolute -left-28 -top-24 h-80 w-80 rounded-full bg-mawar-300/40 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-20 top-28 h-72 w-72 rounded-full bg-mawar-200/60 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-60 w-60 rounded-full bg-mawar-100/70 blur-3xl"></div>
    <div class="pointer-events-none absolute left-1/2 top-1/2 hidden h-[34rem] w-[34rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-mawar-300/40 lg:block"></div>
    <div class="pointer-events-none absolute left-1/2 top-1/2 hidden h-[44rem] w-[44rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-mawar-200/50 lg:block"></div>

    <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">

        <h1 class="animate-fade-up text-[2.2rem] font-extrabold leading-[1.08] tracking-tight text-slate-900 [animation-delay:80ms] sm:text-5xl lg:text-[3.6rem]">
            Selamat Datang di Layanan Kesehatan Masyarakat
            <span class="mt-1 block bg-gradient-to-r from-mawar-800 via-mawar-600 to-mawar-500 bg-clip-text pb-3 leading-[1.25] text-transparent">
                {{ $profil['nama'] }}
            </span>
        </h1>

        <p class="animate-fade-up mx-auto mt-7 max-w-2xl text-base leading-relaxed text-slate-600 [animation-delay:160ms] sm:text-lg">
            {{ $profil['deskripsi'] }}
        </p>

        <p class="animate-fade-up mt-3 text-sm font-medium text-mawar-700 [animation-delay:180ms]">
            {{ $profil['wilayah'] }}
        </p>

        {{-- CTA --}}
        <div class="animate-fade-up mt-10 flex flex-wrap items-center justify-center gap-3 [animation-delay:240ms]">
            <a href="#statistik"
               class="group inline-flex items-center gap-2 rounded-full bg-mawar-800 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-mawar-900/25 transition hover:-translate-y-0.5 hover:bg-mawar-700">
                Lihat Statistik
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                     viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <a href="#jadwal"
               class="inline-flex items-center gap-2 rounded-full border border-mawar-300 bg-white/70 px-7 py-3.5 text-sm font-semibold text-mawar-800 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Jadwal Kegiatan
            </a>
        </div>
    </div>
</section>


{{-- ══════════════════ BENTO: PROFIL & DESKRIPSI POSYANDU MAWAR 1 ══════════════════ --}}
<section id="profil" class="mx-auto max-w-6xl scroll-mt-28 px-4 pt-16 sm:px-6 lg:px-8">

    {{-- Header Section --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <span class="text-base font-bold uppercase tracking-widest text-mawar-600">Tentang Kami</span>
            <h2 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Profil Posyandu</h2>
        </div>
        <span class="hidden h-px flex-1 bg-gradient-to-r from-mawar-200 to-transparent sm:ml-8 sm:block"></span>
    </div>

    {{-- Bento Grid Layout --}}
    <div class="grid gap-6 lg:grid-cols-12 lg:items-stretch">

        {{-- 1. Kartu Utama (Kiri): Gambaran Umum & Foto Bersama --}}
        <article class="relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-mawar-900 via-mawar-800 to-mawar-950 p-7 text-white shadow-xl shadow-mawar-950/10 lg:col-span-7 sm:p-9">
            {{-- Light Accents Background --}}
            <div class="pointer-events-none absolute -right-12 -top-12 h-64 w-64 rounded-full bg-mawar-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-mawar-600/15 blur-2xl"></div>

            <div class="relative z-10">

                <h3 class="mt-6 text-2xl font-black tracking-tight text-white sm:text-3xl">
                    {{ $profil['nama'] }}
                </h3>

                <p class="mt-3 flex items-start gap-2 text-sm font-medium leading-relaxed text-mawar-100/90 sm:text-base">
                    <svg class="mt-1 h-4 w-4 shrink-0 text-mawar-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    {{ $profil['dusun_rt_rw'] }}, {{ $profil['desa'] }}, {{ $profil['kecamatan'] }}
                </p>

                <p class="mt-4 text-sm leading-relaxed text-mawar-100/85 sm:text-base">
                   Sebagai garda terdepan pelayanan kesehatan masyarakat berbasis siklus hidup —
                    melayani balita, remaja, usia dewasa, lanjut usia, hingga ibu hamil dan nifas
                    secara holistik.
                </p>

                {{-- Preview Foto Bersama --}}
                <div class="group relative mt-6 overflow-hidden rounded-2xl border border-white/15 bg-white/5 shadow-inner">
                    <img src="{{ asset('images/fotobersamaposyandu.jpg') }}"
                         alt="Foto Bersama Kader Posyandu Mawar 1"
                         class="h-48 w-full object-cover transition-transform duration-500 group-hover:scale-105 sm:h-56">
                    <div class="absolute inset-0 bg-gradient-to-t from-mawar-950/80 via-transparent to-transparent"></div>
                    <span class="absolute bottom-3 left-4 text-xs font-medium text-mawar-100/90">
                        Kader & Pengurus Posyandu Mawar 1
                    </span>
                </div>
            </div>

            <div class="relative z-10 mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-4 text-xs text-mawar-200">
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-mawar-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ filled($profil['jam']) ? $profil['jam'] : $profil['jadwal'] }}
                </span>
                <span class="font-medium text-mawar-300">{{ $profil['wilayah'] }}</span>
            </div>
        </article>

        {{-- 2. Kartu Detail Layanan & Digitalisasi (Kanan) --}}
        <div class="flex flex-col gap-6 lg:col-span-5">

            {{-- Sub-kartu 1: Alur Pelayanan --}}
            <article class="flex-1 rounded-3xl border border-mawar-100 bg-white p-6 shadow-sm transition hover:shadow-md sm:p-7">
                <h3 class="text-lg font-bold text-slate-900">Alur Pelayanan Terpadu</h3>

                <p class="mt-3 text-xs font-medium leading-relaxed text-slate-700 sm:text-sm">
                    Setiap kegiatan rutin dilaksanakan setiap bulan dengan alur yang tertata: diawali presensi warga, dilanjutkan pengukuran fisik (BB, TB, LP, TD, Gula Darah), skrining kesehatan (PTM, TBC, SKILAS), hingga pencatatan rekam medis dan penyuluhan gizi.
                </p>
            </article>

            {{-- Sub-kartu 2: Digitalisasi Sistem Informasi --}}
            <article class="flex-1 rounded-3xl border border-mawar-100 bg-gradient-to-br from-mawar-50/60 via-white to-white p-6 shadow-sm transition hover:shadow-md sm:p-7">
                <h3 class="text-lg font-bold text-slate-900">Sistem Informasi Berbasis Web</h3>

                <p class="mt-3 text-xs font-medium leading-relaxed text-slate-700 sm:text-sm">
                    Melalui inovasi sistem terintegrasi, Posyandu Mawar 1 mengotomatisasi perhitungan status gizi (IMT), mutasi kategori usia otomatis, pencatatan imunisasi balita permanen, hingga kalkulasi grafik kesehatan warga secara real-time.
                </p>
            </article>
        </div>
    </div>
</section>

{{-- ══════════════════ JADWAL KEGIATAN ══════════════════ --}}
<section id="jadwal" class="mx-auto max-w-6xl scroll-mt-24 px-4 pt-16 sm:px-6 lg:px-8">

    {{-- Header & Sasaran --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-base font-bold uppercase tracking-widest text-mawar-600">Agenda Kegiatan</span>
            <h2 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Jadwal Pemeriksaan</h2>
            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">Pelaksanaan rutin bulanan untuk seluruh warga.</p>
        </div>

        {{-- Tag Kategori Sasaran --}}
        <div class="flex flex-wrap items-center gap-1.5 rounded-2xl bg-mawar-50/80 p-2 ring-1 ring-mawar-100">
            <span class="px-2 text-[10px] font-bold uppercase text-mawar-800">Sasaran:</span>
            @foreach ($kategori as $k)
                <span class="rounded-xl bg-white px-2.5 py-1 text-xs font-semibold text-mawar-700 shadow-sm ring-1 ring-mawar-200/50">{{ $k }}</span>
            @endforeach
        </div>
    </div>

    {{-- Layout 2 Kolom Seimbang --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-12 lg:items-stretch">

        {{-- 1. Banner Informasi (Kiri - 5 Kolom) --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-mawar-800 via-mawar-700 to-mawar-900 p-6 text-white shadow-lg shadow-mawar-900/10 lg:col-span-5 sm:p-7">
            <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-mawar-100 ring-1 ring-white/20 backdrop-blur-md">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    Informasi Penting
                </span>

                <h3 class="mt-4 text-xl font-bold tracking-tight text-white">
                    Pengumuman Tanggal Pelaksanaan
                </h3>

                <p class="mt-2 text-xs font-medium leading-relaxed text-mawar-100/90 sm:text-sm">
                    {{ $profil['jadwal'] }} Kepastian tanggal dan jam pelayanan dikirimkan secara berkala
                    oleh kader melalui grup/status WhatsApp warga Padukuhan {{ $profil['dusun_rt_rw'] }}
                    dan informasi melalui masjid saat hari pelaksanaan.
                </p>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 border-t border-white/15 pt-4 text-xs font-semibold text-mawar-200">
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pantau terus grup WA Posyandu Mawar 1
            </div>
        </div>

        {{-- 2. Kartu Kegiatan Terakhir (Kanan - 7 Kolom) --}}
        <div class="lg:col-span-7">
            @if ($kegiatanTerakhir)
                <article class="relative flex h-full flex-col justify-between overflow-hidden rounded-3xl border
                                border-mawar-200 bg-white p-6 shadow-xl shadow-mawar-900/5 transition-all
                                duration-300 hover:border-mawar-300 sm:p-7">

                    <div>
                        {{-- Top Header Kartu --}}
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl
                                        bg-mawar-800 text-center text-white shadow-md shadow-mawar-800/20">
                                <span class="text-2xl font-black leading-none tabular-nums">
                                    {{ $kegiatanTerakhir->day }}
                                </span>
                                <span class="mt-1 text-[10px] font-bold uppercase tracking-wider opacity-90">
                                    {{ $kegiatanTerakhir->translatedFormat('M') }}
                                </span>
                            </div>

                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1
                                         text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                Kegiatan Terakhir
                            </span>
                        </div>

                        {{-- Info Detail --}}
                        <div class="mt-5">
                            <span class="text-xs font-bold uppercase tracking-wider text-mawar-700">
                                {{ $kegiatanTerakhir->translatedFormat('F Y') }}
                            </span>
                            <h3 class="mt-0.5 text-xl font-black text-slate-900 sm:text-2xl">
                                {{ $profil['nama'] }}
                            </h3>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-xs
                                font-semibold text-slate-600 sm:text-sm">
                        <span class="flex items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-xl bg-mawar-50 text-mawar-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            {{ filled($profil['jam']) ? $profil['jam'] : '08.00 – 12.00 WIB' }}
                        </span>

                        <span class="text-xs font-medium text-slate-400">{{ $profil['nama'] }}</span>
                    </div>
                </article>
            @else
                <div class="flex h-full flex-col items-center justify-center rounded-3xl border-2 border-dashed
                            border-slate-200 bg-white p-10 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Belum ada kegiatan tercatat</p>
                    <p class="mt-1 text-sm text-slate-400">Jadwal kegiatan akan tampil setelah ada kegiatan Posyandu.</p>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ══════════════════ INFO STRIP ══════════════════ --}}
<div class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 rounded-full border border-mawar-100 bg-mawar-50 py-3 pl-3 pr-6">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11.25v5.25m0-8.25h.008v.008H12V8.25z" />
            </svg>
        </span>
        <p class="text-xs leading-relaxed text-mawar-800/90">
            Statistik kesehatan agregat <strong class="font-semibold">(tanpa identitas warga)</strong>.
            Grafik di bawah memakai data yang sama dengan dashboard kader.
        </p>
    </div>
</div>


{{-- ══════════════════ FILTER + GRAFIK ══════════════════ --}}
<section id="statistik" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6 lg:px-8">

    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-base font-bold uppercase tracking-widest text-mawar-600">Statistik</span>
            </div>
            <h2 class="mt-2.5 text-3xl font-extrabold tracking-tight text-slate-900">Kesehatan Warga</h2>
            <p class="mt-1.5 text-sm text-slate-400">
                Periode {{ $periode->label() }}
                @if ($filterImt !== 'semua')
                    &middot; {{ \App\Services\DashboardService::FILTER_IMT[$filterImt] }}
                @endif
            </p>
        </div>

        {{-- Filter periode (BENAR-FUNGSI) --}}
        <form method="GET" action="{{ route('beranda') }}"
              class="flex items-center gap-2.5 rounded-full border border-slate-200 bg-white p-1.5 pl-5 shadow-md shadow-mawar-900/5">
            @if ($filterImt !== 'semua')
                <input type="hidden" name="kategori" value="{{ $filterImt }}">
            @endif

            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Periode</span>

            <select name="bulan" aria-label="Bulan" onchange="this.form.submit()"
                    class="input w-32 rounded-full border-0 bg-mawar-50 py-1.5 text-sm font-medium text-slate-700 ring-0 focus:ring-0">
                @for ($b = 1; $b <= 12; $b++)
                    <option value="{{ $b }}" @selected($b === $periode->bulan)>
                        {{ \Carbon\CarbonImmutable::create(2000, $b, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>

            <select name="tahun" aria-label="Tahun" onchange="this.form.submit()"
                    class="input w-24 rounded-full border-0 bg-mawar-50 py-1.5 text-sm font-medium text-slate-700 ring-0 focus:ring-0">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}" @selected($t === $periode->tahun)>{{ $t }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Peringatan: belum ada kegiatan --}}
    @unless ($statistik['ada_kegiatan'])
        <div class="mt-8 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-amber-900">
                Belum ada kegiatan Posyandu pada <strong>{{ $periode->label() }}</strong>.
                Coba pilih periode lain.
            </p>
        </div>
    @endunless

    {{-- 4 grafik (DATA ASLI dari backend) --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        @foreach ($statistik['grafik'] as $kunci => $isi)
            <x-ui.chart-card
                :judul="$isi['judul']"
                :subjudul="$isi['keterangan']"
                :bars="array_map(fn ($b) => ['label' => $b['label'], 'value' => $b['nilai']], $isi['batang'])">

                @if ($kunci === 'imt')
                    <x-slot:footer>
                        <form method="GET" action="{{ route('beranda') }}">
                            <input type="hidden" name="bulan" value="{{ $periode->bulan }}">
                            <input type="hidden" name="tahun" value="{{ $periode->tahun }}">
                            <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()"
                                    class="input w-32 shrink-0 border-0 py-1.5 text-xs ring-1 ring-mawar-200 focus:ring-mawar-500">
                                @foreach (\App\Services\DashboardService::FILTER_IMT as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected($filterImt === $nilai)>
                                        {{ $nilai === 'semua' ? 'Semua Kategori' : $label }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:footer>
                @endif
            </x-ui.chart-card>
        @endforeach
    </div>
</section>


{{-- ══════════════════ FOOTER ══════════════════ --}}
<footer class="relative mt-4 overflow-hidden rounded-t-[2.5rem] bg-mawar-900 sm:rounded-t-[4rem]">
    <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-mawar-700/40 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-16 h-72 w-72 rounded-full bg-mawar-800/60 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-4 pb-8 pt-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 md:grid-cols-[1.4fr_0.8fr_1.3fr]">

            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logoposyandu.png') }}" alt="Logo {{ $profil['nama'] }}" class="h-10 w-10 rounded-full bg-white object-contain p-1">
                    <span class="text-[15px] font-bold text-white">{{ $profil['nama'] }}</span>
                </div>
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-mawar-300/90">
                    Sistem Informasi Pengelolaan Data Posyandu Terintegrasi Berbasis Web
                    untuk mendukung pendataan dan pelaporan kesehatan masyarakat.
                </p>
                <p class="mt-5 max-w-sm rounded-2xl bg-white/5 px-4 py-3 text-xs leading-relaxed text-mawar-300 ring-1 ring-white/10">
                    {{ $profil['alamat'] }}
                </p>
            </div>

            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-mawar-300">Navigasi</p>
                <ul class="mt-5 space-y-3 text-sm">
                    <li><a href="#profil" class="text-mawar-400 transition hover:text-white">Profil Posyandu</a></li>
                    <li><a href="#jadwal" class="text-mawar-400 transition hover:text-white">Jadwal Kegiatan</a></li>
                    <li><a href="#kontak" class="text-mawar-400 transition hover:text-white">Kontak</a></li>
                    <li><a href="#statistik" class="text-mawar-400 transition hover:text-white">Statistik</a></li>
                </ul>
            </div>

            {{-- ═══ PETA LOKASI ═══ --}}
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mawar-200">
                    Lokasi Posyandu
                </p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                    <iframe
                        src="https://www.google.com/maps?q=Posyandu%20Mawar%201%2C%20Tegalyoso%2C%20Banyuraden%2C%20Kec.%20Gamping%2C%20Sleman%2C%20DIY&output=embed"
                        class="h-56 w-full"
                        style="border:0"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Lokasi Posyandu Mawar 1"></iframe>
                </div>

                <a href="https://www.google.com/maps/search/?api=1&query=Posyandu+Mawar+1,+Tegalyoso,+Sleman"
                   target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-mawar-300 transition hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    Buka di Google Maps
                </a>
            </div>
        </div>

        <div class="mt-14 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 sm:flex-row">
            <p class="text-[11px] text-mawar-500">
                &copy; {{ date('Y') }} Universitas Muhammadiyah Yogyakarta &middot; Capstone Project
            </p>
            <p class="text-[11px] text-mawar-500">Dibangun oleh Tim Maura &middot; Mariska &middot; Rafly &middot; Irfan</p>
        </div>
    </div>
</footer>

</x-layouts.public>