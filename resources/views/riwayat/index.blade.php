@php
    $warnaKategori = ['balita' => 'amber', 'remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet'];
@endphp


<x-layouts.app title="Riwayat Pemeriksaan" active="riwayat">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-5">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">Daftar Warga Riwayat Pemeriksaan</h2>
        <p class="mt-1 text-sm text-slate-500">
            Seluruh warga. Filter kategori, lalu klik <strong>Lihat Riwayat</strong> untuk
            detail per kunjungan.
        </p>
    </div>


    {{-- ═══════════ ERROR ═══════════ --}}
    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-rose-800">{{ $errors->first() }}</p>
        </div>
    @endif


    {{-- ═══════════ PENCARIAN + FILTER ═══════════ --}}
    <form method="GET" action="{{ route('riwayat.index') }}"
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

        <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()"
                class="input py-2 text-[13px] sm:w-52">
            <option value="">Filter Kategori (semua)</option>
            @foreach (\App\Enums\KategoriUsia::options() as $nilai => $label)
                <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

        @if ($cari !== '' || $kategoriDipilih)
            <a href="{{ route('riwayat.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
        @endif
    </form>


    {{-- ═══════════ TABEL ═══════════ --}}
    <div class="card overflow-hidden">

        @if ($daftarWarga->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ ($cari !== '' || $kategoriDipilih) ? 'Warga tidak ditemukan' : 'Belum ada data warga' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    {{ ($cari !== '' || $kategoriDipilih)
                        ? 'Coba ubah kata kunci atau pilih kategori lain.'
                        : 'Mulai dengan menambahkan warga baru.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Nama Warga</th>
                            <th class="th">NIK</th>
                            <th class="th">Kategori Usia</th>
                            <th class="th">Kunjungan Terakhir</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($daftarWarga as $warga)
                            @php $kategori = $warga->kategori_usia_terkini; @endphp

                            <tr class="transition hover:bg-mawar-50/40">
                                <td class="td text-slate-400">{{ $daftarWarga->firstItem() + $loop->index }}</td>

                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full
 bg-mawar-100 text-[11px] font-bold uppercase text-mawar-700">
                                            {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
                                        </span>
                                        <span class="font-semibold text-slate-900">{{ $warga->nama_warga }}</span>
                                    </div>
                                </td>

                                <td class="td">
                                    <code class="rounded bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-700">
                                        {{ $warga->nik }}
                                    </code>
                                </td>

                                <td class="td">
                                    <x-ui.badge :warna="$warnaKategori[$kategori->value] ?? 'mawar'">
                                        {{ $kategori->label() }}
                                    </x-ui.badge>
                                </td>

                                <td class="td text-[13px]">
                                    @if ($warga->kunjungan_terakhir)
                                        <span class="font-medium text-slate-700">
                                            {{ \Illuminate\Support\Carbon::parse($warga->kunjungan_terakhir)->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Belum pernah</span>
                                    @endif
                                </td>

                                <td class="td">
                                    <div class="flex justify-end">
                                        <a href="{{ route('riwayat.warga', $warga->nik) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-mawar-700 px-3.5 py-1.5
 text-[12px] font-bold text-white shadow-sm transition hover:-translate-y-px hover:bg-mawar-800">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            Lihat Riwayat
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($daftarWarga->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                    <p class="text-[12px] text-slate-500">
                        Menampilkan <strong class="font-bold text-slate-700">{{ $daftarWarga->firstItem() }}–{{ $daftarWarga->lastItem() }}</strong>
                        dari <strong class="font-bold text-slate-700">{{ $daftarWarga->total() }}</strong> warga
                    </p>
                    <div>{{ $daftarWarga->links() }}</div>
                </div>
            @endif
        @endif
    </div>


    {{-- ═══════════ INFO ═══════════ --}}
    <p class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[12px] leading-relaxed text-slate-500">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <span>
            Setiap pemeriksaan yang sudah di-<strong>Submit</strong>, serta setiap tambahan centang
            vaksin/ASI balita per kunjungan, tercatat sebagai riwayat. Filter kategori mempersempit
            daftar. Klik <strong>Lihat Riwayat</strong> untuk membuka detail per tanggal.
        </span>
    </p>

</x-layouts.app>