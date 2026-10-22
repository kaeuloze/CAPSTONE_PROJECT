@php
    $keterangan = [
        'remaja'          => 'IMT, lingkar perut, tekanan darah, HB, penglihatan, dan pendengaran',
        'ibu_hamil_nifas' => 'Pemeriksaan kehamilan, nifas, dan menyusui',
        'dewasa_lansia'   => 'IMT, lingkar perut, tekanan darah, gula darah, TBC, dan PUMA',
    ];

    $ikon = [
        'remaja'          => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM12 9.75h.008v.008H12V9.75z',
        'ibu_hamil_nifas' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM12 9.75h.008v.008H12V9.75z',
        'dewasa_lansia'   => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM12 9.75h.008v.008H12V9.75z',
    ];

    $warna = [
        'remaja'          => 'sky',
        'ibu_hamil_nifas' => 'rose',
        'dewasa_lansia'   => 'mawar',
    ];
@endphp


<x-layouts.app title="Rekapitulasi" active="rekap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-6">
        <h2 class="text-lg font-bold tracking-tight text-slate-900">Pilih Kategori Rekapitulasi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Setiap rekap mengikuti format resmi Kemenkes dan terisi otomatis dari hasil pemeriksaan.
        </p>
    </div>


    {{-- ═══════════ 3 KARTU KATEGORI ═══════════ --}}
    <div class="grid gap-5 md:grid-cols-3">

        @foreach ($daftarKategori as $i => $kategori)
            <a href="{{ route('rekapitulasi.show', $kategori) }}"
               class="group animate-fade-up relative overflow-hidden rounded-2xl border border-slate-200/80
                      bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1
                      hover:border-mawar-200 hover:shadow-xl hover:shadow-mawar-900/5"
               style="animation-delay: {{ $i * 80 }}ms">

                {{-- Aksen garis atas --}}
                <span class="absolute inset-x-6 top-0 h-0.5 origin-left scale-x-0 rounded-full
                             bg-gradient-to-r from-mawar-400 to-mawar-800 transition-transform
                             duration-500 group-hover:scale-x-100"></span>

                {{-- Ikon --}}
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-mawar-50 text-mawar-700
                             ring-1 ring-mawar-200/70 transition duration-300 group-hover:scale-110
                             group-hover:bg-mawar-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </span>

                {{-- Judul --}}
                <h3 class="mt-5 text-base font-bold text-slate-900">{{ $kategori->label() }}</h3>

                {{-- Keterangan --}}
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">
                    {{ $keterangan[$kategori->value] ?? '' }}
                </p>

                {{-- Panah --}}
                <span class="mt-5 inline-flex items-center gap-1.5 text-[13px] font-bold text-mawar-700
                             transition-transform group-hover:translate-x-1">
                    Lihat Rekapitulasi
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </a>
        @endforeach
    </div>


    {{-- ═══════════ INFO ═══════════ --}}
    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-mawar-100 bg-mawar-50/70 px-4 py-3.5">
        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-mawar-600 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v.008M12 11.25h.008M12 15h.008" />
            </svg>
        </span>
        <p class="text-xs leading-relaxed text-mawar-800/85">
            Menekan sebuah kartu membuka tabel rekap kategori tersebut (dengan filter Bulan/Tahun).
            Tampilan awal: periode kegiatan terakhir
            (<strong class="font-semibold">{{ $periodeBawaan->label() }}</strong>).
        </p>
    </div>

</x-layouts.app>