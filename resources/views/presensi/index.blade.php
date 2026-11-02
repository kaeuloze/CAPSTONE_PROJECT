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

            {{-- Ekspor daftar hadir: hanya warga Hadir (permintaan kader 31 Okt 2026) --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('presensi.excel', $kegiatan) }}" class="btn btn-secondary py-2 text-[13px]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Ekspor ke Excel (.xlsx)
                </a>
                {{-- Langsung membuka dialog cetak (lewat bingkai tersembunyi, js/presensi-cetak.js);
                     tanpa JavaScript tautan membuka halaman cetak di tab baru yang juga langsung mencetak. --}}
                <a href="{{ route('presensi.cetak', $kegiatan) }}" target="_blank" rel="noopener" data-cetak-langsung
                   class="btn btn-primary py-2 text-[13px]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Cetak Daftar Hadir
                </a>
            </div>
        </div>

        {{-- ═══════════ PESAN KEGIATAN SELESAI (hook #pesan-selesai) ═══════════ --}}
        <div id="pesan-selesai" role="alert" @if ($berlangsung) hidden @endif
             class="mb-5 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>

            <div class="text-[13px] leading-relaxed text-sky-900">
                @if ($berlangsung)
                    Kegiatan ini baru saja diselesaikan. Presensi tidak bisa diubah lagi.
                @else
                    Kegiatan sudah selesai, sehingga status kehadiran hanya dapat
                    <strong class="font-semibold">dilihat</strong>. Warga yang belum ditandai saat kegiatan
                    diselesaikan tercatat <strong class="font-semibold text-rose-700">"Tidak Hadir"</strong>.
                    Untuk koreksi, buka kembali kegiatan lewat tombol
                    <strong class="font-semibold">Edit</strong> di tab Arsip Kegiatan
                    (ubah status menjadi <strong class="font-semibold">Berlangsung</strong>).
                @endif
            </div>
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
                    belum diselesaikan. Saat <strong>"Selesaikan Kegiatan"</strong> ditekan, sisa warga
                    otomatis tercatat <strong>"Tidak Hadir"</strong>.
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

    {{-- ═══════════ JS LIVE-UPDATE ═══════════ --}}
    <script src="{{ asset('js/presensi.js') }}" defer></script>
    <script src="{{ asset('js/presensi-cetak.js') }}" defer></script>

</x-layouts.app>