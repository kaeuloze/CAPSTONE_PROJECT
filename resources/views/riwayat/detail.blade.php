@php
    $warnaKategori = ['remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet',
                      'ibu_hamil' => 'rose', 'ibu_nifas' => 'rose'];

    // Status "tidak diperiksa" per tab: jenis tab (Rutin / 6 Bulan / Tahunan) tidak ada di jenis yang dilakukan.
    $tidakDiperiksa = fn (array $tab) => ! in_array(
        ($tab['jenis'] ?? \App\Enums\JenisPemeriksaan::Rutin)->value, $jenisDilakukan, true);
    $berlaku = fn (string $kunci) => $definisi->bagian()[$kunci]->berlakuUntuk($warga, $pemeriksaan->tanggal_pemeriksaan);
@endphp


<x-layouts.app title="Detail Pemeriksaan" active="riwayat"
 x-data="{ hapus: false }">

    <div class="mx-auto max-w-4xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('riwayat.warga', $warga->nik) }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke riwayat {{ $warga->nama_warga }}
        </a>

        {{-- ═══════════ HEADER ═══════════ --}}
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900">
                    Detail Pemeriksaan
                </h2>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span class="font-semibold text-slate-700">
                        {{ $pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y') }}
                    </span>
                    <span>·</span>
                    <span>{{ $kegiatan->nama_kegiatan }}</span>
                </p>
            </div>

            @if ($definisi)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('riwayat.edit', $pemeriksaan) }}" class="btn btn-primary py-2 text-[13px]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        Edit
                    </a>
                    
                    <button type="button"
                            @click="$dispatch('konfirmasi-hapus', {
                                judul: 'Yakin hapus data pemeriksaan?',
                                pesan: 'Pemeriksaan tanggal {{ $pemeriksaan->tanggal_pemeriksaan->translatedFormat('j F Y') }} akan dihapus permanen.',
                                aksi: 'Ya, Hapus',
                                url: '{{ route('riwayat.destroy', $pemeriksaan) }}',
                                metode: 'DELETE'
                            })"
                            class="btn bg-rose-600 py-2 text-[13px] text-white shadow-sm hover:bg-rose-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916" />
                        </svg>
                        Hapus
                    </button>
                </div>
            @endif
        </div>


        {{-- ═══════════ IDENTITAS WARGA ═══════════ --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl
 border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-mawar-100
 text-base font-bold uppercase text-mawar-700">
                    {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
                </span>

                <div class="min-w-0">
                    <p class="text-base font-extrabold tracking-tight text-slate-900">{{ $warga->nama_warga }}</p>
                    <p class="mt-0.5 font-mono text-[12px] text-slate-500">{{ $warga->nik }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <x-ui.badge :warna="$warnaKategori[$kategori->value] ?? 'mawar'">
                    {{ $kategori->label() }}
                </x-ui.badge>
                <x-ui.badge warna="slate">Usia {{ $usia }} tahun</x-ui.badge>
</div>
        </div>

        <p class="mt-2 flex items-center gap-1.5 px-1 text-[12px] text-slate-500">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Kehadiran: <strong>Hadir</strong> (tercatat dari presensi kegiatan)
        </p>


        {{-- ═══════════ ISI ═══════════ --}}
        @if (! $definisi)
            {{-- Fallback kalau definisi form kategori ini belum tersedia --}}
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3.5">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <p class="text-sm font-medium text-sky-900">
                    Tampilan detail untuk kategori <strong>{{ $kategori->label() }}</strong>
                    sedang disiapkan bersama formulirnya.
                </p>
            </div>
        @else

            {{-- Ringkasan jenis pemeriksaan --}}
            @if ($jenisDilakukan)
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        Jenis yang dilakukan:
                    </span>
                    @foreach ($jenisDilakukan as $j)
                        <x-ui.badge warna="mawar">{{ \App\Enums\JenisPemeriksaan::tryFrom($j)?->label() ?? $j }}</x-ui.badge>
                    @endforeach
                </div>
            @endif

            {{-- ═══════════ HASIL OTOMATIS ═══════════
                 Status yang dihitung sistem saat data disimpan (App\Services\Pemeriksaan\HasilOtomatis). --}}
            @if ($hasilOtomatis)
                <div class="mt-4 card overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-3.5">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Hasil Otomatis</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">Dihitung sistem dari isian pemeriksaan di bawah.</p>
                    </div>
                    <dl class="grid gap-x-6 gap-y-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($hasilOtomatis as $hasil)
                            <div class="min-w-0">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $hasil['label'] }}</dt>
                                <dd class="mt-1">
                                    <x-ui.badge :warna="$hasil['risiko'] ? 'rose' : 'emerald'">{{ $hasil['teks'] }}</x-ui.badge>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- ═══════════ KARTU PER BAGIAN ═══════════
                 Dikelompokkan per tab formulir. Isi kartu memakai tampilan formulir pemeriksaan dalam mode baca
                 ($modeBaca), sehingga label dan pilihan jawaban sama persis dengan formulir (0/1 → Tidak/Ya, kode → teks). --}}
            <style>
                .rincian-baca h2, .rincian-baca .ket, .rincian-baca .hasil-otomatis, .rincian-baca .skor-otomatis { display: none; }
                .rincian-baca .kisi-aks, .rincian-baca div.isian, .rincian-baca [data-kelompok-skilas] { display: contents; }
                .rincian-baca h3.sub-kelompok { grid-column: 1 / -1; margin-top: .25rem; font-size: 11px; font-weight: 700;
                    text-transform: uppercase; letter-spacing: .05em; color: rgb(100 116 139); }
            </style>

            @php
                $kartu = function (string $kunciBagian) use ($definisi, $nilai) {
                    return ['bagian' => $definisi->bagian()[$kunciBagian], 'kunci' => $kunciBagian, 'isi' => $nilai[$kunciBagian] ?? []];
                };
            @endphp

            @foreach ($definisi->tab() as $kunciTab => $tab)
                @continue(isset($tabNonaktif[$kunciTab]))
                @php $daftarBagian = array_values(array_filter($tab['bagian'], $berlaku)); @endphp
                @continue($daftarBagian === [])

                @if (count($definisi->tab()) > 1)
                    <h3 class="mt-6 px-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        {{ $tab['label'] }}
                    </h3>
                @endif

                @if ($tidakDiperiksa($tab))
                    <div class="mt-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[13px] italic text-slate-500">
                        {{ \Illuminate\Support\Str::start($tab['label'], 'Pemeriksaan ') }} tidak dilakukan pada kunjungan ini.
                    </div>
                @else
                    @foreach ($daftarBagian as $kunciBagian)
                        @include('riwayat.partials.kartu-bagian', $kartu($kunciBagian))
                    @endforeach
                @endif
            @endforeach

            {{-- Bagian di bawah semua tab (mis. Penyuluhan & Rujukan). --}}
            @php $bagianUmum = array_values(array_filter($definisi->bagianUmum(), $berlaku)); @endphp
            @foreach ($bagianUmum as $kunciBagian)
                @include('riwayat.partials.kartu-bagian', $kartu($kunciBagian))
            @endforeach
        @endif


        {{-- ═══════════ INFO ═══════════ --}}
        <p class="mt-5 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
                  text-[12px] leading-relaxed text-slate-500">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <span>
                Koreksi data hasil pemeriksaan yang sudah tersimpan dilakukan                <strong>di halaman ini</strong> — bukan di menu Pemeriksaan.
            </span>
        </p>
    </div>

</x-layouts.app>