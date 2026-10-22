@php
    $jeda = max(2, (int) config('posyandu.presensi.polling_detik'));
    $pesanKosong = $cari !== ''
        ? 'Tidak ada balita yang cocok dengan pencarian.'
        : 'Belum ada balita yang hadir. Balita muncul di sini setelah ditandai Hadir di menu Presensi.';
@endphp


<x-layouts.app title="Vaksinasi Balita" active="vaksinasi">

    <div id="antrean-balita"
         data-url-status="{{ route('vaksinasi.status') }}"
         data-id-kegiatan="{{ $kegiatan->id_kegiatan }}"
         data-jeda="{{ $jeda * 1000 }}">

        {{-- ═══════════ HEADER ═══════════ --}}
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">Daftar Vaksinasi Balita</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Daftar balita otomatis (kategori Balita). Klik <strong>Buka</strong> untuk
                    mengisi checklist vaksin dari Buku KIA.
                </p>
            </div>

            <x-ui.badge warna="mawar">{{ $kegiatan->nama_kegiatan }}</x-ui.badge>
        </div>


        {{-- ═══════════ INFO ═══════════ --}}
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-amber-500 text-white">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
                </svg>
            </span>
            <p class="text-xs leading-relaxed text-amber-900">
                Halaman ini otomatis hanya menampilkan warga kategori <strong>Balita</strong> yang sudah
                <strong>Hadir</strong>. Catatan centang bulan sebelumnya <strong>tetap tersimpan</strong>
                (progressive record) — formnya tidak pernah kosong.
            </p>
        </div>


        {{-- ═══════════ PENCARIAN ═══════════ --}}
        <form method="GET" action="{{ route('vaksinasi.index') }}"
              class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center" role="search">

            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
                </svg>
                <input type="search" name="cari" value="{{ $cari }}" maxlength="100"
                       placeholder="Cari nama / NIK balita..."
                       aria-label="Cari nama atau NIK balita"
                       class="input py-2 pl-9 text-[13px]" />
            </div>

            <button type="submit" class="btn btn-primary py-2 text-[13px]">Cari</button>

            @if ($cari !== '')
                <a href="{{ route('vaksinasi.index') }}" class="btn btn-secondary py-2 text-[13px]">Reset</a>
            @endif
        </form>


        {{-- ═══════════ TABEL ═══════════ --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Nama Balita</th>
                            <th class="th">NIK</th>
                            <th class="th">Status</th>
                            <th class="th text-right">Aksi</th>
                        </tr>
                    </thead>

                    {{-- HOOK #isi-balita — JS mengganti isinya tiap polling --}}
                    <tbody id="isi-balita" data-pesan-kosong="{{ $pesanKosong }}">
                        @forelse ($daftar as $balita)
                            <tr>
                                <td>{{ $balita->nama_warga }}</td>
                                <td>{{ $balita->nik }}</td>
                                <td><span class="badge hadir">Hadir</span></td>
                                <td>
                                    <a href="{{ route('vaksinasi.form', $balita->nik) }}" class="tombol kecil abu">
                                        Buka
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="kosong">{{ $pesanKosong }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($daftar->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $daftar->links() }}
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
    <script src="{{ asset('js/vaksinasi-daftar.js') }}" defer></script>

</x-layouts.app>