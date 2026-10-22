@php
    $modeEdit  = $akun->exists;
    $sandiMin  = (int) config('posyandu.pengguna.sandi_min');
    $sandiMaks = (int) config('posyandu.pengguna.sandi_maks');
@endphp


<x-layouts.app :title="$modeEdit ? 'Edit Akun Kader' : 'Tambah Akun Kader'" active="pengguna">

    <div class="mx-auto max-w-2xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('pengguna.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar akun
        </a>

        <h2 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">
            {{ $modeEdit ? 'Edit Akun Kader' : 'Tambah Akun Kader' }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $modeEdit
                ? 'Ubah data akun petugas yang dapat mengakses sistem.'
                : 'Buat akun petugas baru yang dapat mengakses sistem.' }}
        </p>


        {{-- ═══════════ ERROR ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    Akun belum bisa disimpan. Periksa kembali username dan kata sandi:
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        {{-- ═══════════ FORM ═══════════ --}}
        <form method="POST" id="form-pengguna" class="mt-6" novalidate
              onsubmit="this.querySelector('[type=submit]').disabled = true; this.querySelector('[type=submit]').textContent = 'Menyimpan...';"
              action="{{ $modeEdit ? route('pengguna.update', $akun->getKey()) : route('pengguna.store') }}">
            @csrf
            @if ($modeEdit)
                @method('PUT')
                {{-- Penanda versi (pengaman edit antar-kader) --}}
                <input type="hidden" name="versi" value="{{ old('versi', $akun->updated_at?->format('Y-m-d H:i:s')) }}">
            @endif

            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Data Akun Petugas</h3>

                {{-- Nama kader --}}
                <div class="mt-5">
                    <label for="nama_kader" class="label">Nama Kader <span class="text-rose-500">*</span></label>
                    <input id="nama_kader" name="nama_kader" type="text" required maxlength="100" autocomplete="off"
                           value="{{ old('nama_kader', $akun->nama_kader) }}"
                           placeholder="Contoh: Ibu Yuli"
                           class="input py-2.5 @error('nama_kader') !ring-rose-400 @enderror" />
                    @error('nama_kader')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Username --}}
                <div class="mt-4">
                    <label for="username" class="label">Username <span class="text-rose-500">*</span></label>
                    <input id="username" name="username" type="text" required minlength="3" maxlength="50"
                           pattern="[a-z0-9._]+" autocapitalize="none" spellcheck="false" autocomplete="off"
                           value="{{ old('username', $akun->username) }}"
                           placeholder="Contoh: yuli"
                           class="input py-2.5 font-mono @error('username') !ring-rose-400 @enderror" />
                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Huruf kecil, angka, titik (.), atau garis bawah (_); 3–50 karakter, tanpa spasi.
                    </p>
                    @error('username')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kata sandi --}}
                <div class="mt-4">
                    <label for="password" class="label">
                        Kata Sandi
                        @unless ($modeEdit)<span class="text-rose-500">*</span>@endunless
                    </label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password" name="password" type="password"
                               minlength="{{ $sandiMin }}" maxlength="{{ $sandiMaks }}"
                               @required(! $modeEdit) autocomplete="new-password"
                               x-bind:type="show ? 'text' : 'password'"
                               placeholder="{{ $modeEdit ? 'Kosongkan bila tidak diubah' : 'Minimal ' . $sandiMin . ' karakter' }}"
                               class="input py-2.5 pr-11 @error('password') !ring-rose-400 @enderror" />

                        <button type="button" @click="show = !show"
                                class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg p-2
                                       text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700"
                                aria-label="Tampilkan kata sandi">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="show" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                        Minimal {{ $sandiMin }} karakter. Boleh berupa kalimat dengan spasi agar mudah diingat.
                        @if ($modeEdit) <strong>Kosongkan</strong> bila kata sandi tidak diubah. @endif
                        Kata sandi diisi sendiri oleh kader pemilik akun.
                    </p>

                    @error('password')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi --}}
                <div class="mt-4">
                    <label for="password_confirmation" class="label">
                        Konfirmasi Kata Sandi
                        @unless ($modeEdit)<span class="text-rose-500">*</span>@endunless
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           maxlength="{{ $sandiMaks }}" @required(! $modeEdit) autocomplete="new-password"
                           class="input py-2.5 @error('password_confirmation') !ring-rose-400 @enderror" />
                    @error('password_confirmation')
                        <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>


            {{-- ═══════════ TOMBOL ═══════════ --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Akun' }}
                </button>

                <a href="{{ route('pengguna.index') }}" class="btn btn-secondary py-2.5">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>