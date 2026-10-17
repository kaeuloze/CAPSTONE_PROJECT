<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Posyandu Mawar 1</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="relative flex min-h-full items-center justify-center overflow-hidden
             bg-mawar-50 px-4 py-12 antialiased">

    {{-- ─── Dekorasi latar ─── --}}
    <div class="pointer-events-none absolute inset-0 opacity-60"
         style="background-image: radial-gradient(circle at 1px 1px, rgb(8 51 68 / .07) 1px, transparent 0);
                background-size: 26px 26px;"></div>
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-mawar-200/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-20 h-72 w-72 rounded-full bg-mawar-300/30 blur-3xl"></div>


    {{-- ══════════════════ KARTU ══════════════════ --}}
    <div class="animate-fade-up relative w-full max-w-sm">

        {{-- ─── Logo ─── --}}
        <div class="flex justify-center">
            <img src="{{ asset('images/logoposyandu.png') }}"
                 alt="Logo Posyandu Mawar 1"
                 class="h-16 w-auto rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-slate-200">
        </div>

        {{-- ─── Nama ─── --}}
        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold tracking-tight text-mawar-900">
                Posyandu Mawar 1
            </h1>
            <p class="mt-0.5 text-xs text-mawar-700/70">
                Tegalyoso, Banyuraden, Sleman
            </p>
        </div>

        {{-- ─── Form ─── --}}
        <div class="mt-7 rounded-2xl border border-slate-200/80 bg-white/95 p-6 shadow-lg shadow-mawar-900/5 backdrop-blur">

            <div class="text-center">
                <h2 class="text-base font-semibold text-slate-900">Masuk ke Sistem</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Gunakan akun kader yang telah terdaftar
                </p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">

                {{-- Username --}}
                <div>
                    <label for="username" class="label">Username</label>
                    <input id="username" name="username" type="text" required autofocus
                           autocomplete="username"
                           class="input py-2.5"
                           placeholder="Masukkan username" />
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="label">Kata Sandi</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password" name="password" required
                               autocomplete="current-password"
                               class="input py-2.5 pr-11"
                               placeholder="Masukkan kata sandi"
                               x-bind:type="show ? 'text' : 'password'" />

                        <button type="button" @click="show = !show"
                                class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg p-2
 text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700"
                                aria-label="Tampilkan kata sandi">
                            <svg x-show="!show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>

                            <svg x-show="show" x-cloak class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Opsi --}}
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remember"
                               class="h-4 w-4 rounded border-slate-300 text-mawar-600 focus:ring-mawar-500">
                        Ingat saya
                    </label>

                    <a href="#" class="text-xs font-medium text-mawar-700 transition hover:text-mawar-900">
                        Lupa kata sandi?
                    </a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary w-full py-2.5">
                    Masuk
                </button>
            </form>
        </div>

        {{-- ─── Kembali ─── --}}
        <p class="mt-5 text-center">
            <a href="{{ route('landing') }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium text-mawar-700 transition hover:text-mawar-900">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke beranda
            </a>
        </p>

        {{-- ─── Copyright ─── --}}
        <p class="mt-4 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} Posyandu Mawar 1 &middot; Capstone Project
        </p>

    </div>

</body>
</html>
