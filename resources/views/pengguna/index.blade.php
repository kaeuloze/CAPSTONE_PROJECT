@php
    $totalAkun = $daftarAkun->total();
@endphp


<x-layouts.app title="Manajemen Pengguna" active="pengguna">

    {{-- ═══════════ FLASH PESAN ═══════════ --}}
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


    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Kelola Data Akun</h2>
            <p class="mt-1 text-sm text-slate-500">
                Daftar kader / petugas yang dapat masuk ke sistem. Mendukung beberapa kader
                bekerja bersamaan dari perangkat berbeda.
            </p>
        </div>

        <a href="{{ route('pengguna.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Akun
        </a>
    </div>


    {{-- ═══════════ PENCARIAN ═══════════ --}}
    <form method="GET" action="{{ route('pengguna.index') }}"
          class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center" role="search">

        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
            </svg>
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100"
                   placeholder="Cari nama kader atau username..."
                   aria-label="Cari nama kader atau username"
                   class="input py-2 pl-9 text-[13px]" />
        </div>

        <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

        @if ($cari !== '')
            <a href="{{ route('pengguna.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
        @endif
    </form>


    {{-- ═══════════ TABEL AKUN ═══════════ --}}
    <div class="card overflow-hidden">

        @if ($daftarAkun->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>

                <p class="mt-4 text-sm font-semibold text-slate-900">
                    {{ $cari !== '' ? 'Akun tidak ditemukan' : 'Belum ada akun kader' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    {{ $cari !== '' ? 'Coba kata kunci lain.' : 'Klik "Tambah Akun" untuk membuat akun pertama.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Nama Kader</th>
                            <th class="th">Username</th>
                            <th class="th">Dibuat</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($daftarAkun as $i => $akun)
                            @php $saya = auth()->id() === $akun->getKey(); @endphp

                            <tr class="transition hover:bg-mawar-50/40">
                                <td class="td text-slate-400">{{ $daftarAkun->firstItem() + $loop->index }}</td>

                                {{-- Nama + badge "Anda" --}}
                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full
                                                     bg-mawar-100 text-[11px] font-bold uppercase text-mawar-700">
                                            {{ \Illuminate\Support\Str::substr($akun->nama_kader ?? '?', 0, 2) }}
                                        </span>

                                        <div class="min-w-0">
                                            <p class="flex items-center gap-2 font-semibold text-slate-900">
                                                {{ $akun->nama_kader }}
                                                @if ($saya)
                                                    <x-ui.badge warna="mawar">Anda</x-ui.badge>
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-slate-400">
                                                Diperbarui {{ $akun->updated_at?->translatedFormat('d M Y H:i') ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="td">
                                    <code class="rounded bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-700">
                                        {{ $akun->username }}
                                    </code>
                                </td>

                                <td class="td text-[13px] text-slate-600">
                                    {{ $akun->created_at?->translatedFormat('d M Y') ?? '—' }}
                                </td>

                                {{-- Aksi --}}
                                <td class="td">
                                    <div class="flex justify-end gap-1.5">

                                        {{-- Edit --}}
                                        <a href="{{ route('pengguna.edit', $akun->getKey()) }}"
                                           title="Edit akun"
                                           class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px]
                                                  font-bold text-slate-700 transition hover:border-mawar-300 hover:bg-mawar-50 hover:text-mawar-800">
                                            Edit
                                        </a>

                                        {{-- Hapus --}}
                                        <button type="button"
                                                @click="$dispatch('konfirmasi-hapus', {
    judul: 'Yakin hapus akun?',
    pesan: @js('Akun '.$akun->nama_kader.' ('.$akun->username.') tidak dapat masuk lagi setelah dihapus.'.($akun->id_kader === auth()->id() ? ' Ini akun yang sedang Anda pakai — Anda akan langsung keluar.' : '')),
    aksi: 'Ya, Hapus',
    url: @js(route('pengguna.destroy', $akun))
})"
                                                title="Hapus akun"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px]
                                                       font-bold text-slate-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($daftarAkun->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                    <p class="text-[12px] text-slate-500">
                        Menampilkan <strong class="font-bold text-slate-700">{{ $daftarAkun->firstItem() }}–{{ $daftarAkun->lastItem() }}</strong>
                        dari <strong class="font-bold text-slate-700">{{ $totalAkun }}</strong> akun
                    </p>
                    <div>{{ $daftarAkun->links() }}</div>
                </div>
            @endif
        @endif
    </div>


    {{-- ═══════════ INFO ═══════════ --}}
    <p class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
              text-[12px] leading-relaxed text-slate-500">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <span>
            Setiap kader dapat masuk dengan akun masing-masing dan bekerja dari perangkat berbeda
            secara bersamaan. Akun yang sedang dipakai tidak bisa dihapus.
        </span>
    </p>

</x-layouts.app>