{{--
    ==========================================================
    KELOLA DATA WARGA  ·  SKF-04 & SKF-05
    Posyandu Mawar 1 - Capstone Project 2 (Frontend)
    Gambar 2.50 (Kelola Data Warga)
    ==========================================================
    Data dari WargaController@index:
      $daftarWarga (paginator) · $cari · $kategoriDipilih (enum|null)
    ==========================================================
--}}

@php
    $warnaKategori = [
        'balita' => 'amber',
        'remaja' => 'sky',
        'dewasa' => 'mawar',
        'lansia' => 'violet',
    ];
@endphp


<x-layouts.app title="Kelola Data Warga" active="warga">


    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Data Warga</h2>
            <p class="mt-1 text-sm text-slate-500">
                Badge kategori usia muncul otomatis berdasarkan tanggal lahir                (mutasi real-time sesuai novelty sistem).
            </p>
        </div>

        <a href="{{ route('warga.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Warga
        </a>
    </div>


    {{-- ═══════════ PENCARIAN + FILTER (SKF-04) ═══════════ --}}
    <form method="GET" action="{{ route('warga.index') }}"
 class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center" role="search">

        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
            </svg>
            <input type="search" name="cari" value="{{ $cari }}" maxlength="100"
                   placeholder="Cari nama atau NIK warga..."
                   aria-label="Cari nama atau NIK warga"
                   class="input py-2 pl-9 text-[13px]" />
        </div>

        <select name="kategori" aria-label="Filter Kategori" class="input py-2 text-[13px] sm:w-52">
            <option value="">Semua Kategori</option>
            @foreach (\App\Enums\KategoriUsia::options() as $nilai => $label)
                <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

        @if ($cari !== '' || $kategoriDipilih)
            <a href="{{ route('warga.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
        @endif
    </form>


    {{-- ═══════════ TABEL ═══════════ --}}
    <div class="card overflow-hidden">

        @if ($daftarWarga->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-6-1.535a11.954 11.954 0 01-.037-.666A11.944 11.944 0 0112 18c2.7 0 5.258 1.03 7.22 2.687M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a11.953 11.953 0 017.38 0M15 7.5a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </span>

                <p class="mt-4 text-sm font-semibold text-slate-900">
                    {{ ($cari !== '' || $kategoriDipilih)
                        ? 'Warga tidak ditemukan'
                        : 'Belum ada data warga' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    {{ ($cari !== '' || $kategoriDipilih)
                        ? 'Coba ubah kata kunci atau pilih kategori lain.'
                        : 'Mulai dengan menambahkan warga baru.' }}
                </p>

                @if ($cari !== '' || $kategoriDipilih)
                    <a href="{{ route('warga.index') }}" class="btn btn-secondary mt-5 py-2 text-[13px]">
                        Reset Pencarian
                    </a>
                @else
                    <a href="{{ route('warga.create') }}" class="btn btn-primary mt-5 py-2 text-[13px]">
                        Tambah Warga Pertama
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Nama Warga</th>
                            <th class="th">NIK</th>
                            <th class="th">Tanggal Lahir</th>
                            <th class="th">Kategori Usia</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($daftarWarga as $warga)
                            @php
                                $kategori = $warga->kategori_usia_terkini;
                                $kondisi = $warga->status_kondisi;
                                $warna = $warnaKategori[$kategori->value] ?? 'mawar';
                            @endphp

                            <tr class="transition hover:bg-mawar-50/40">

                                <td class="td text-slate-400">{{ $daftarWarga->firstItem() + $loop->index }}</td>

                                {{-- Nama + JK --}}
                                <td class="td">
                                    <div class="flex items-center gap-3">
                                        <span @class([
                                            'grid h-9 w-9 shrink-0 place-items-center rounded-full text-[11px] font-bold uppercase',
                                            'bg-rose-100 text-rose-700'   => $warga->jenis_kelamin === 'perempuan',
                                            'bg-mawar-100 text-mawar-700' => $warga->jenis_kelamin !== 'perempuan',
                                        ])>
                                            {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
                                        </span>

                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">{{ $warga->nama_warga }}</p>
                                            <p class="text-[11px] text-slate-400">
                                                {{ $warga->jenis_kelamin ? 'Laki-laki' : 'Perempuan' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="td">
                                    <code class="rounded bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-700">
                                        {{ $warga->nik }}
                                    </code>
                                </td>

                                <td class="td text-[13px] text-slate-600">
                                    {{ \Illuminate\Support\Carbon::parse($warga->tanggal_lahir)->translatedFormat('d M Y') }}
                                </td>

                                {{-- Kategori + kondisi khusus --}}
                                <td class="td">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <x-ui.badge :warna="$warna">{{ $kategori->label() }}</x-ui.badge>

                                        @if ($kondisi && $kondisi->value !== 'normal')
                                            <x-ui.badge warna="rose">{{ $kondisi->label() }}</x-ui.badge>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td class="td">
                                    <div class="flex justify-end gap-1.5">

                                        {{-- Lihat --}}
                                        <a href="{{ route('warga.show', $warga->nik) }}"
                                           title="Lihat detail"
                                           class="rounded-lg p-2 text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('warga.edit', $warga->nik) }}"
                                           title="Edit data"
                                           class="rounded-lg p-2 text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </a>

                                        {{-- Hapus --}}
                                        <button type="button"
                                                @click="$dispatch('konfirmasi-hapus', {
    judul: 'Yakin hapus data warga?',
    pesan: @js('Data '.$warga->nama_warga.' beserta seluruh riwayat pemeriksaan akan dihapus permanen.'),
    aksi: 'Ya, Hapus',
    url: @js(route('warga.destroy', $warga))
})"
                                                title="Hapus data"
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            {{-- ═══════════ PAGINATION ═══════════ --}}
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

</x-layouts.app>