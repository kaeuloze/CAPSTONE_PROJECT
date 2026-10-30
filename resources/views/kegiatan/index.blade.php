<x-layouts.app title="Kelola Kegiatan" active="kegiatan">


    {{-- ═══════════════ TAB ═══════════════ --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200">
        <nav class="-mb-px flex gap-6">
            <a href="{{ route('kegiatan.index') }}"
               @class([
                   'border-b-2 px-1 pb-3 text-sm font-semibold transition',
                   'border-mawar-700 text-mawar-800'      => $tab === 'berlangsung',
                   'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $tab !== 'berlangsung',
               ])>
                Kegiatan Berlangsung
            </a>

            <a href="{{ route('kegiatan.index', ['tab' => 'arsip']) }}"
               @class([
                   'flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-semibold transition',
                   'border-mawar-700 text-mawar-800'      => $tab === 'arsip',
                   'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $tab !== 'arsip',
               ])>
                Arsip Kegiatan
                <span @class([
                    'rounded-full px-2 py-0.5 text-[10px] font-bold',
                    'bg-mawar-100 text-mawar-700' => $tab === 'arsip',
                    'bg-slate-100 text-slate-500'  => $tab !== 'arsip',
                ])>{{ $jumlahArsip }}</span>
            </a>
        </nav>

        <a href="{{ route('kegiatan.create') }}" class="btn btn-primary mb-2 py-2 text-[13px]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Buat Kegiatan Baru
        </a>
    </div>


    @if ($tab === 'berlangsung')

        {{-- ══════════════════════════════════════════════════════ GAMBAR 2.42 — BELUM ADA KEGIATAN
             ══════════════════════════════════════════════════════ --}}
        @if (! $aktif)
            <x-empty-state
                icon="calendar"
                judul="Belum ada kegiatan berlangsung"
                deskripsi="Belum ada kegiatan Posyandu yang sedang berjalan saat ini. Buat kegiatan baru untuk memulai pencatatan kehadiran warga."
                :cta="['label' => 'Buat Kegiatan Baru', 'url' => route('kegiatan.create')]" />
        @else

            {{-- ══════════════════════════════════════════════════════
                 GAMBAR 2.43 — KEGIATAN SEDANG BERLANGSUNG
                 ══════════════════════════════════════════════════════ --}}
            <div class="grid gap-5 lg:grid-cols-12">

                {{-- ─── Kartu utama ─── --}}
                <article class="relative overflow-hidden rounded-2xl border border-mawar-800/40 bg-gradient-to-br from-mawar-900 via-mawar-800 to-mawar-950
                                p-6 text-white shadow-xl shadow-mawar-900/20 lg:col-span-7 sm:p-7">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-mawar-500/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-12 h-48 w-48 rounded-full bg-mawar-400/10 blur-2xl"></div>

                    <div class="relative">
                        {{-- Status pill --}}
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-wider ring-1 ring-white/20 backdrop-blur">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-mawar-300 opacity-75"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-mawar-300"></span>
                            </span>
                            Sedang Berlangsung
                        </span>

                        <h2 class="mt-4 text-xl font-extrabold tracking-tight sm:text-2xl">
                            {{ $aktif->nama_kegiatan }}
                        </h2>

                        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-mawar-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            {{ \Carbon\Carbon::parse($aktif->tanggal_kegiatan)->translatedFormat('l, d F Y') }}
                        </p>

                        {{-- Statistik kehadiran --}}
                        <div class="mt-6 grid grid-cols-3 gap-3 rounded-xl bg-white/[.07] p-4 ring-1 ring-white/10 backdrop-blur-sm">
                            <x-ui.mini-stat label="Total Warga" :nilai="$ringkasan['total']" class="text-white" />
                            <x-ui.mini-stat label="Hadir" :nilai="$ringkasan['hadir']" class="text-emerald-300" />
                            <x-ui.mini-stat label="Belum Hadir" :nilai="$ringkasan['tidak_hadir']" class="text-amber-300" />
                        </div>

                        {{-- Progress --}}
                        <div class="mt-4">
                            <x-ui.progress :nilai="$ringkasan['hadir']" :total="$ringkasan['total']"
 warna="mawar" tinggi="h-2.5" />
                            <p class="mt-1.5 text-[11px] text-mawar-300">
                                {{ $ringkasan['total'] > 0 ? round($ringkasan['hadir'] / $ringkasan['total'] * 100) : 0 }}%
                                warga sudah hadir
                            </p>
                        </div>

                        {{-- Peringatan pemeriksaan draft --}}
                        @if ($belumSubmit > 0)
                            <p class="mt-4 flex items-start gap-2 rounded-lg bg-amber-400/15 px-3 py-2.5
                                      text-[12px] font-medium text-amber-100 ring-1 ring-amber-300/25">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                Masih ada <strong class="mx-0.5">{{ $belumSubmit }}</strong> pemeriksaan
                                yang belum di-Submit.
                            </p>
                        @endif

                        {{-- Aksi --}}
                        <div class="mt-6 flex flex-wrap gap-2.5">
                            <a href="{{ route('presensi.index', $aktif) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5
 text-sm font-bold text-mawar-900 shadow-lg transition                                      hover:-translate-y-0.5 hover:bg-mawar-50 hover:shadow-xl">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Presensi
                            </a>

                            {{-- Selesaikan Kegiatan (revisi): sisa warga otomatis "Tidak Hadir". Dialog: x-confirm-dialog. --}}
                            <button type="button"
                                    @click="$dispatch('konfirmasi-hapus', {
                                        judul: @js('Selesaikan kegiatan '.$aktif->nama_kegiatan.'?'),
                                        pesan: 'Warga yang belum presensi akan otomatis tercatat &quot;Tidak Hadir&quot;, dan kegiatan ini pindah ke tab Arsip Kegiatan.',
                                        peringatan: @js($belumSubmit > 0
                                            ? 'Masih ada '.$belumSubmit.' pemeriksaan yang belum di-Submit. Datanya tetap tersimpan dan dapat dilanjutkan dengan membuka kembali kegiatan dari Arsip.'
                                            : ''),
                                        aksi: 'Ya, Selesaikan',
                                        tone: 'warning',
                                        url: @js(route('kegiatan.selesaikan', $aktif)),
                                        metode: 'POST'
                                    })"
                                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm
                                           font-bold text-white shadow-lg transition
                                           hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-xl">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Selesaikan Kegiatan
                            </button>

                            <a href="{{ route('kegiatan.edit', $aktif) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm
                                    font-bold text-white ring-1 ring-white/25 backdrop-blur transition
                                    hover:-translate-y-0.5 hover:bg-white/25 hover:shadow-lg">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                                Edit Kegiatan
                            </a>
                        </div>
                    </div>
                </article>

                {{-- ─── Proporsi per kategori (SKF-04) ─── --}}
                <article class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-5 sm:p-7">
                    <h3 class="text-sm font-bold text-slate-900">Kehadiran per Kategori</h3>
                    <p class="mt-1 text-xs text-slate-400">Hanya warga berstatus Hadir yang bisa diperiksa</p>

                    <ul class="mt-5 space-y-4">
                        @php
                            $warnaKategori = [
                                'balita' => 'bg-amber-500',
                                'remaja' => 'bg-sky-500',
                                'dewasa' => 'bg-mawar-600',
                                'lansia' => 'bg-violet-500',
                                'ibu'    => 'bg-rose-500',
                            ];
                        @endphp

                        @foreach ($perKelompok as $key => $k)
                            <li>
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="flex items-center gap-2 text-[13px] font-semibold text-slate-700">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $warnaKategori[$key] ?? 'bg-mawar-600' }}"></span>
                                        {{ $k['label'] }}
                                    </span>
                                    <span class="text-[13px] tabular-nums text-slate-500">
                                        <strong class="font-bold text-slate-900">{{ $k['hadir'] }}</strong>
                                        / {{ $k['total'] }}
                                    </span>
                                </div>

                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $warnaKategori[$key] ?? 'bg-mawar-600' }}"
                                         style="width: {{ $k['total'] > 0 ? round($k['hadir'] / $k['total'] * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </article>
            </div>
        @endif

    @else

        {{-- ══════════════════════════════════════════════════════
             GAMBAR 2.46 — ARSIP KEGIATAN
             ══════════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden">

            {{-- Search + filter --}}
            <form method="GET" action="{{ route('kegiatan.index', ['tab' => 'arsip']) }}"
                  class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:flex-row sm:items-center">

                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
                    </svg>
                    <input type="search" name="cari" value="{{ $cari }}"
                           placeholder="Cari nama kegiatan..."
                           class="input py-2 pl-9 text-[13px]" />
                </div>

                <select name="tahun" class="input w-full py-2 text-[13px] sm:w-36">
                    <option value="">Semua Tahun</option>
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($tahunDipilih == $t)>{{ $t }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary py-2 text-[13px]">Terapkan</button>

                @if ($cari !== '' || $tahunDipilih)
                    <a href="{{ route('kegiatan.index', ['tab' => 'arsip']) }}"
                       class="btn btn-secondary py-2 text-[13px]">Reset</a>
                @endif
            </form>

            {{-- Tabel --}}
            @if ($daftar->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-900">Belum ada kegiatan selesai</p>
                    <p class="mt-1 text-sm text-slate-400">
                        @if ($cari !== '' || $tahunDipilih)
                            Tidak ada kegiatan yang cocok dengan filter.
                        @else
                            Arsip akan terisi setelah kegiatan pertama diselesaikan.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="th">Nama Kegiatan</th>
                                <th class="th">Tanggal</th>
                                <th class="th">Kehadiran</th>
                                <th class="th text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($daftar as $g)
                                <tr class="transition hover:bg-mawar-50/40">
                                    <td class="td">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-mawar-50 text-mawar-700 ring-1 ring-mawar-200/70">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                            </span>
                                            <span class="font-semibold text-slate-900">{{ $g->nama_kegiatan }}</span>
                                        </div>
                                    </td>

                                    <td class="td text-[13px] text-slate-600">
                                        {{ \Carbon\Carbon::parse($g->tanggal_kegiatan)->translatedFormat('d M Y') }}
                                    </td>

                                    <td class="td">
                                        <div class="flex items-center gap-3">
                                            <div class="w-24">
                                                <x-ui.progress :nilai="$g->jumlah_hadir" :total="$g->jumlah_tercatat" tinggi="h-1.5" />
                                            </div>
                                            <span class="whitespace-nowrap text-[13px] tabular-nums text-slate-600">
                                                <strong class="font-bold text-slate-900">{{ $g->jumlah_hadir }}</strong>
                                                / {{ $g->jumlah_tercatat }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="td">
                                        <div class="flex justify-end gap-1.5">
                                            <a href="{{ route('presensi.index', $g) }}"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200
 bg-white px-3 py-1.5 text-[12px] font-semibold text-slate-700
                                                      transition hover:border-mawar-300 hover:bg-mawar-50 hover:text-mawar-800">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Lihat Presensi
                                            </a>

                                            <a href="{{ route('kegiatan.edit', $g) }}"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200
                                                      bg-white px-3 py-1.5 text-[12px] font-semibold text-slate-700
                                                      transition hover:border-mawar-300 hover:bg-mawar-50 hover:text-mawar-800">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                </svg>
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($daftar->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $daftar->links() }}
                    </div>
                @endif
            @endif
        </div>
    @endif

</x-layouts.app>