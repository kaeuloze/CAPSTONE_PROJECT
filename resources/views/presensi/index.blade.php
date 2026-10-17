{{--
    ==========================================================
    PRESENSI / DETAIL KEGIATAN  ·  SKF-06
    Posyandu Mawar 1 - Capstone Project 2 (Frontend)
    Gambar 2.45 (pencatatan presensi) & 2.47 (arsip, read-only)
    ==========================================================
    PENTIK: hook #presensi, #notifikasi, #indikator-langsung,
    #jumlah-total, #jumlah-hadir, #jumlah-tidak, #pesan-selesai,
    #label-status, #tombol-selesaikan, tr[data-nik],
    .status-hadir, .form-presesi  ->  dipakai public/js/presensi.js
    ==========================================================
--}}

@php
    $berlangsung = (bool) $kegiatan->status_kegiatan;
    $jeda = max(2, (int) config('posyandu.presensi.polling_detik'));
@endphp


<x-layouts.app :title="$kegiatan->nama_kegiatan" active="kegiatan">

    <div id="presensi"
         data-url-status="{{ route('presensi.status', $kegiatan) }}"
         data-jeda="{{ $jeda * 1000 }}"
         data-berlangsung="{{ $berlangsung ? '1' : '0' }}">

        {{-- ═══════════ HEADER ═══════════ --}}
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">

            <div class="min-w-0">
                <a href="{{ route('kegiatan.index', ['tab' => $berlangsung ? 'berlangsung' : 'arsip']) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ $berlangsung ? 'Kembali ke daftar kegiatan' : 'Kembali ke Arsip Kegiatan' }}
                </a>

                <h2 class="mt-2 flex flex-wrap items-center gap-3 text-xl font-extrabold tracking-tight text-slate-900">
                    {{ $kegiatan->nama_kegiatan }}
                    <span id="label-status"
                          class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $berlangsung
 ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
                                        : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }}">
                        {{ $kegiatan->labelStatus() }}
                    </span>
                </h2>

                <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    {{ $kegiatan->tanggalLengkap() }}
                </p>
            </div>

            @if ($berlangsung)
                <button type="button" id="tombol-selesaikan"
                        @click="$dispatch('konfirmasi-selesaikan')"
                        class="btn bg-amber-500 py-2.5 text-[13px] text-white shadow-sm hover:bg-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Selesaikan Kegiatan
                </button>
            @endif
        </div>


        {{-- ═══════════ PESAN KEGIATAN SELESAI (hook #pesan-selesai) ═══════════ --}}
        <div id="pesan-selesai" class="pesan mb-5" role="alert" @if ($berlangsung) hidden @endif>
            @if ($berlangsung)
                Kegiatan ini baru saja diselesaikan. Presensi tidak bisa diubah lagi.
            @else
                Kegiatan sudah selesai, sehingga status kehadiran hanya dapat<strong>dilihat</strong>.
                Warga yang belum ditandai saat kegiatan diselesaikan tercatat                <strong>"Tidak Hadir"</strong>. Untuk koreksi, buka kembali kegiatan lewat tombol
                <strong>Edit</strong> di tab Arsip Kegiatan (ubah status menjadi <strong>Berlangsung</strong>).
            @endif
        </div>


        {{-- ═══════════ RINGKASAN (hook #jumlah-total/hadir/tidak) ═══════════ --}}
        <div class="mb-5 grid gap-4 sm:grid-cols-3" aria-live="polite">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Warga</p>
                <p id="jumlah-total" class="mt-2 text-3xl font-extrabold tabular-nums leading-none text-slate-800">
                    {{ $ringkasan['total'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/60 p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Hadir</p>
                <p id="jumlah-hadir" class="mt-2 text-3xl font-extrabold tabular-nums leading-none text-emerald-700">
                    {{ $ringkasan['hadir'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-rose-200/70 bg-rose-50/60 p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">
                    {{ $berlangsung ? 'Belum Hadir' : 'Tidak Hadir' }}
                </p>
                <p id="jumlah-tidak" class="mt-2 text-3xl font-extrabold tabular-nums leading-none text-rose-700">
                    {{ $ringkasan['tidak_hadir'] }}
                </p>
            </div>
        </div>


        {{-- ═══════════ PENCARIAN + FILTER (SKF-04) ═══════════ --}}
        <form method="GET" action="{{ route('presensi.index', $kegiatan) }}"
 class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center" role="search">

            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
                </svg>
                <input type="search" name="cari" value="{{ $cari }}" maxlength="100"
                       placeholder="Cari nama / NIK warga..."
                       aria-label="Cari nama atau NIK warga"
                       class="input py-2 pl-9 text-[13px]" />
            </div>

            <select name="kategori" aria-label="Filter Kategori"
                    onchange="this.form.submit()"
                    class="input py-2 text-[13px] sm:w-56">
                <option value="">Filter Kategori (semua)</option>
                @foreach (\App\Enums\KategoriUsia::options() as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

            @if ($cari !== '' || $kategoriDipilih)
                <a href="{{ route('presensi.index', $kegiatan) }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
            @endif
        </form>


        {{-- ═══════════ INFO ═══════════ --}}
        @if ($berlangsung)
            <p class="mb-4 flex items-start gap-2.5 rounded-xl border border-mawar-100 bg-mawar-50/60 px-4 py-3
 text-[13px] leading-relaxed text-mawar-900">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-mawar-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
 d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>
                    Salah pencet? Status presensi bisa di-toggle <strong>Hadir ↔ Belum</strong> selama kegiatan
                    belum diselesaikan. Saat <strong>"Selesaikan Kegiatan"</strong> ditekan, sisa warga                    otomatis tercatat <strong>"Tidak Hadir"</strong>.
                </span>
            </p>
        @endif


        {{-- ═══════════ TABEL WARGA ═══════════ --}}
        <div class="card overflow-hidden">
            @if ($daftar->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ ($cari !== '' || $kategoriDipilih)
                            ? 'Tidak ada warga yang cocok'
                            : 'Belum ada data warga' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ ($cari !== '' || $kategoriDipilih)
                            ? 'Coba ubah kata kunci atau pilih filter kategori lain.'
                            : 'Data warga akan tampil setelah terdaftar di sistem.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="th w-14">No</th>
                                <th class="th">Nama Warga</th>
                                <th class="th">Kategori</th>
                                <th class="th">Status Kehadiran</th>
                                @if ($berlangsung)
                                    <th class="th text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($daftar as $warga)
                                @php $hadir = (bool) $warga->hadir; @endphp

                                <tr data-nik="{{ $warga->nik }}"
                                    class="transition {{ $hadir ? 'bg-emerald-50/30 hover:bg-emerald-50/60' : 'hover:bg-slate-50' }}">

                                    <td class="td text-slate-400">
                                        {{ $daftar->firstItem() + $loop->index }}
                                    </td>

                                    <td class="td">
                                        <div class="flex items-center gap-3">
                                            <span @class([
                                                'grid h-9 w-9 shrink-0 place-items-center rounded-full text-[11px] font-bold uppercase',
                                                'bg-mawar-100 text-mawar-700' => $warga->jenis_kelamin !== 'perempuan',
                                                'bg-rose-100 text-rose-700'   => $warga->jenis_kelamin === 'perempuan',
                                            ])>{{ substr($warga->nama_warga, 0, 2) }}</span>

                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900">{{ $warga->nama_warga }}</p>
                                                <p class="font-mono text-[11px] text-slate-400">{{ $warga->nik }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="td">
                                        <x-ui.badge warna="mawar">
                                            {{ \App\Enums\KategoriUsia::dariTanggalLahir($warga->tanggal_lahir, $kegiatan->tanggal_kegiatan)->label() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="td">
                                        {{-- HOOK .status-hadir — JS menukar .hadir/.belum/.tidak --}}
                                        <span class="status-hadir {{ $hadir ? 'hadir' : ($berlangsung ? 'belum' : 'tidak') }}">
                                            {{ $hadir ? 'Hadir' : ($berlangsung ? 'Belum' : 'Tidak Hadir') }}
                                        </span>
                                    </td>

                                    @if ($berlangsung)
                                        <td class="td">
                                            <div class="flex justify-end">
                                                {{-- HOOK .form-presensi — JS menyinkronkan input & tombol --}}
                                                <form method="POST" class="form-presensi"
                                                      action="{{ route('presensi.atur', [$kegiatan, $warga->nik]) }}">
                                                    @csrf
                                                    <input type="hidden" name="hadir" value="{{ $hadir ? '0' : '1' }}">

                                                    <button type="submit" @class([
                                                        'rounded-lg px-3.5 py-1.5 text-[12px] font-bold shadow-sm transition hover:-translate-y-px',
                                                        'bg-mawar-700 text-white hover:bg-mawar-800' => $hadir,
                                                        'abu' => ! $hadir,
                                                    ])>
                                                        {{ $hadir ? 'Batalkan' : 'Tandai Hadir' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
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


        {{-- ═══════════ INFO BAWAH + POLLING ═══════════ --}}
        <div class="mt-5 space-y-2">
            @if ($berlangsung)
                <p id="indikator-langsung" class="flex items-center gap-1.5">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    </span>
                    Diperbarui otomatis setiap {{ $jeda }} detik
                </p>
            @endif

            <p class="text-[11px] leading-relaxed text-slate-400">
                Setelah presensi, warga yang berstatus <strong>Hadir</strong> otomatis muncul di menu
                Pemeriksaan sesuai kategori usianya — hanya yang Hadir yang bisa diperiksa.
            </p>
            <p class="text-[11px] leading-relaxed text-slate-400">
                Saat filter kategori diubah, angka total warga ikut menyesuaikan sesuai kategori yang dipilih.
            </p>
        </div>
    </div>


    {{-- ═══════════ KONTAINER NOTIFIKASI (hook #notifikasi) ═══════════ --}}
    <div id="notifikasi" aria-live="assertive"></div>


    {{-- ═══════════ DIALOG SELESAIKAN ═══════════ --}}
    @if ($berlangsung)
        <div x-data="{ selesai: false }"
             @konfirmasi-selesaikan.window="selesai = true">
            @include('kegiatan.partials.dialog-selesaikan', [
                'kegiatan'    => $kegiatan,
                'belumSubmit' => $belumSubmit,
            ])
        </div>
    @endif

    {{-- ═══════════ JS LIVE-UPDATE ═══════════ --}}
    <script src="{{ asset('js/presensi.js') }}" defer></script>

</x-layouts.app>