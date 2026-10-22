@php
    $jeda = max(2, (int) config('posyandu.presensi.polling_detik'));

    $warnaKategori = [
        'remaja'    => 'sky',
        'dewasa'    => 'mawar',
        'lansia'    => 'violet',
        'ibu_hamil' => 'rose',
        'ibu_nifas' => 'rose',
    ];
@endphp


<x-layouts.app title="Kelola Pemeriksaan" active="pemeriksaan">

    <div id="antrean"
         data-url-status="{{ route('pemeriksaan.status') }}"
         data-id-kegiatan="{{ $kegiatan->id_kegiatan }}"
         data-jeda="{{ $jeda * 1000 }}">

        {{-- ═══════════ HEADER ═══════════ --}}
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">Daftar Pemeriksaan</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Hanya warga yang berstatus <strong>Hadir</strong> pada kegiatan aktif
                    yang ditampilkan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge warna="mawar">{{ $kegiatan->nama_kegiatan }}</x-ui.badge>
                <x-ui.badge warna="slate">
                    {{ \Carbon\Carbon::parse($kegiatan->tanggal_kegiatan)->translatedFormat('d M Y') }}
                </x-ui.badge>
            </div>
        </div>


        {{-- ═══════════ INFO ═══════════ --}}
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-mawar-100 bg-mawar-50/60 px-4 py-3">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
                </svg>
            </span>
            <p class="text-xs leading-relaxed text-mawar-800/85">
                Balita <strong>tidak muncul</strong> di menu ini karena ditangani khusus melalui
                menu <strong>Vaksinasi Balita</strong>. Formulir yang terbuka otomatis menyesuaikan
                kategori usia warga.
            </p>
        </div>


        {{-- ═══════════ PENCARIAN + FILTER ═══════════ --}}
        <form method="GET" action="{{ route('pemeriksaan.index') }}"
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

            <select name="kategori" aria-label="Filter Kategori"
 onchange="this.form.submit()"
                    class="input py-2 text-[13px] sm:w-52">
                <option value="">Semua Kategori</option>
                @foreach (\App\Enums\KategoriPemeriksaan::options() as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($kategoriDipilih?->value === $nilai)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

            @if ($cari !== '' || $kategoriDipilih)
                <a href="{{ route('pemeriksaan.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
            @endif
        </form>


        {{-- ═══════════ TABEL ANTREAN ═══════════ --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Nama Warga</th>
                            <th class="th">NIK</th>
                            <th class="th">Kategori</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    {{-- HOOK #isi-antrean — JS akan mengganti isinya setiap polling --}}
                    <tbody id="isi-antrean"
                           data-pesan-kosong="{{ ($cari !== '' || $kategoriDipilih) ? 'Tidak ada warga yang cocok dengan pencarian atau filter.' : 'Belum ada warga yang berstatus Hadir pada kegiatan ini.' }}">

                        @forelse ($daftar as $warga)
                            @php
                                $kategori = $warga->kategori_form;
                                $draft = $warga->status_pemeriksaan === \App\Enums\StatusPemeriksaan::Draft->value;
                            @endphp

                            <tr class="transition hover:bg-mawar-50/40">
                                <td>{{ $daftar->firstItem() + $loop->index }}</td>
                                <td>{{ $warga->nama_warga }}</td>
                                <td>{{ $warga->nik }}</td>
                                <td>
                                    <span class="badge {{ $warnaKategori[$kategori] === 'sky' ? 'bg-sky-100 text-sky-700 ring-1 ring-sky-200'
        : ($warnaKategori[$kategori] === 'violet' ? 'bg-violet-100 text-violet-700 ring-1 ring-violet-200'
                          : 'bg-mawar-100 text-mawar-700 ring-1 ring-mawar-200') }}">
 {{ \App\Enums\KategoriPemeriksaan::from($kategori)->label() }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('pemeriksaan.form', $warga->nik) }}"
                                       class="tombol kecil {{ $draft ? 'draft' : 'abu' }}">
                                        {{ $draft ? 'Pemeriksaan Belum Selesai' : 'Mulai Pemeriksaan' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="kosong">
                                    {{ ($cari !== '' || $kategoriDipilih) ? 'Tidak ada warga yang cocok dengan pencarian atau filter.' : 'Belum ada warga yang berstatus Hadir pada kegiatan ini.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($daftar->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                    <p class="text-[12px] text-slate-500">
                        Menampilkan <strong class="font-bold text-slate-700">{{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}</strong>
                        dari <strong class="font-bold text-slate-700">{{ $daftar->total() }}</strong> warga
                    </p>
                    <div>{{ $daftar->links() }}</div>
                </div>
            @endif
        </div>


        {{-- ═══════════ POLLING ═══════════ --}}
        <p id="indikator-langsung" class="mt-4 flex items-center gap-1.5">
            <span class="relative flex h-1.5 w-1.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            </span>
            Diperbarui otomatis setiap {{ $jeda }} detik
        </p>
    </div>


    {{-- ═══════════ JS LIVE-UPDATE ═══════════ --}}
    <script src="{{ asset('js/pemeriksaan-daftar.js') }}" defer></script>

</x-layouts.app>