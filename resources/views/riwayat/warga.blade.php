@php
    $warnaKategori = ['remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet',
                      'ibu_hamil' => 'rose', 'ibu_nifas' => 'rose'];
@endphp


<x-layouts.app title="Riwayat Pemeriksaan" active="riwayat">

    {{-- ═══════════ KEMBALI ═══════════ --}}
    <a href="{{ route('riwayat.index') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Kembali ke daftar warga
    </a>

    {{-- ═══════════ IDENTITAS WARGA ═══════════ --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl
 border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-mawar-100
 text-base font-bold uppercase text-mawar-700">
                {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
            </span>

            <div class="min-w-0">
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $warga->nama_warga }}</h2>
                <p class="mt-0.5 font-mono text-[12px] text-slate-500">{{ $warga->nik }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    <x-ui.badge :warna="$warnaKategori[$warga->kategori_usia_terkini->value] ?? 'mawar'">
                        {{ $warga->kategori_usia_terkini->label() }}
                    </x-ui.badge>
                </div>
            </div>
        </div>

        <a href="{{ route('warga.show', $warga->nik) }}" class="btn btn-secondary py-2 text-[13px]">
           <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-6-1.535a11.954 11.954 0 01-.037-.666A11.944 11.944 0 0112 18c2.7 0 5.258 1.03 7.22 2.687M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a11.953 11.953 0 017.38 0M15 7.5a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Detail Warga
        </a>
    </div>


    {{-- ═══════════ RIWAYAT PEMERIKSAAN ═══════════ --}}
    <div class="mt-6">
        <div class="flex items-center gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-mawar-600">
                Riwayat Pemeriksaan
            </p>
            <span class="h-px flex-1 bg-gradient-to-r from-mawar-200 to-transparent"></span>
        </div>

        <p class="mt-1.5 text-xs text-slate-400">
            Klik <strong>Detail</strong> untuk melihat rincian angka pengukuran &amp; skrining.
        </p>

        <div class="card mt-3 overflow-hidden">
            @if ($daftar->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-900">Belum ada pemeriksaan</p>
                    <p class="mt-1 text-sm text-slate-400">
                        Belum ada pemeriksaan yang di-Submit untuk warga ini.
                        @if ($riwayatVaksin !== [])
                            Catatan vaksinasi &amp; ASI ada di tabel bawah.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="th">Tanggal / Kegiatan</th>
                                <th class="th">Kategori saat itu</th>
                                <th class="th">Ringkasan</th>
                                <th class="th text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($daftar as $p)
                                @php
                                    $jenis = collect($p->jenis_pemeriksaan)->map->label()->implode(', ');
                                    $hasil = $katalog->untuk($p->kategori_usia_saat_periksa)?->ringkasan($p) ?? [];
                                    $warna = $warnaKategori[$p->kategori_usia_saat_periksa->value] ?? 'mawar';
                                @endphp

                                <tr class="transition hover:bg-mawar-50/40">
                                    <td class="td">
                                        <p class="font-semibold text-slate-900">
                                            {{ $p->tanggal_pemeriksaan->translatedFormat('j F Y') }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">{{ $p->kegiatan->nama_kegiatan }}</p>
                                    </td>

                                    <td class="td">
                                        <x-ui.badge :warna="$warna">
                                            {{ $p->kategori_usia_saat_periksa->label() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="td text-[13px] text-slate-600">
                                        {{ collect([$jenis])->merge($hasil)->filter()->implode(' · ') ?: '—' }}
                                    </td>

                                    <td class="td">
                                        <div class="flex justify-end">
                                            <a href="{{ route('riwayat.show', $p) }}"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200
 bg-white px-3.5 py-1.5 text-[12px] font-bold text-slate-700 transition hover:border-mawar-300 hover:bg-mawar-50 hover:text-mawar-800">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($daftar->hasPages())
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                        <p class="text-[12px] text-slate-500">
                            Menampilkan <strong class="font-bold text-slate-700">{{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}</strong>
                            dari <strong class="font-bold text-slate-700">{{ $daftar->total() }}</strong> pemeriksaan
                        </p>
                        <div>{{ $daftar->links() }}</div>
                    </div>
                @endif
            @endif
        </div>
    </div>


    {{-- ═══════════ RIWAYAT VAKSINASI & ASI ═══════════ --}}
    @if ($riwayatVaksin !== [])
        <div class="mt-6">
            <div class="flex items-center gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-mawar-600">
                    Riwayat Vaksinasi &amp; ASI Eksklusif
                </p>
                <span class="h-px flex-1 bg-gradient-to-r from-mawar-200 to-transparent"></span>
            </div>

            <p class="mt-1.5 text-xs text-slate-400">Dicatat di menu Vaksinasi Balita.</p>

            <div class="card mt-3 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="th">Tanggal / Kegiatan</th>
                                <th class="th">Kategori</th>
                                <th class="th">Ringkasan</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($riwayatVaksin as $catatan)
                                <tr>
                                    <td class="td">
                                        <p class="font-semibold text-slate-900">
                                            {{ $catatan['tanggal']?->translatedFormat('j F Y') ?? '—' }}
                                        </p>
                                        @if ($catatan['kegiatan'])
                                            <p class="text-[11px] text-slate-400">{{ $catatan['kegiatan'] }}</p>
                                        @endif
                                    </td>

                                    <td class="td">
                                        <x-ui.badge warna="amber">Balita</x-ui.badge>
                                    </td>

                                    <td class="td text-[13px] text-slate-600">
                                        {{ collect([
                                            $catatan['vaksin'] ? 'Vaksinasi: ' . implode(', ', $catatan['vaksin']) : null,
                                            $catatan['asi'] ? 'ASI ' . implode(', ', $catatan['asi']) : null,
                                        ])->filter()->implode(' · ') ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-3 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
                      text-[12px] leading-relaxed text-slate-500">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>
                    Koreksi catatan vaksinasi dan ASI dilakukan di menu
                    <strong>Vaksinasi Balita</strong> saat balita hadir di kegiatan Posyandu.
                </span>
            </p>
        </div>
    @endif

</x-layouts.app>