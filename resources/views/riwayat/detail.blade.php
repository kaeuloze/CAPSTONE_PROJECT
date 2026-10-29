@php
    $warnaKategori = ['remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet',
                      'ibu_hamil' => 'rose', 'ibu_nifas' => 'rose'];

    // Label kolom → bahasa manusia + satuan.
    $labelKolom = [
        'berat_badan' => ['Berat Badan', 'kg'],
        'tinggi_badan' => ['Tinggi Badan', 'cm'],
        'lingkar_perut' => ['Lingkar Perut', 'cm'],
        'lila' => ['LILA (Lingkar Lengan Atas)', 'cm'],
        'tekanan_darah' => ['Tekanan Darah', 'mmHg'],
        'kadar_gula_darah' => ['Gula Darah', 'mg/dL'],
        'kadar_hb' => ['Kadar HB', 'g/dL'],
        'status_imt' => ['Status Gizi (IMT)', null],
        'status_tekanan_darah' => ['Status Tekanan Darah', null],
        'status_lingkar_perut' => ['Status Lingkar Perut', null],
        'status_lila' => ['Status LILA', null],
        'indikator_perilaku' => ['Indikator Perilaku', null],
        'kemampuan_motorik_kognitif' => ['Kemampuan Motorik & Kognitif', null],
        'skor_kebiasaan_merokok' => ['Skor Kebiasaan Merokok', null],
        'gejala_pernapasan' => ['Gejala Pernapasan', null],
        'total_skor_risiko_paru' => ['Total Skor Risiko Paru (PUMA)', null],
        'usia_kehamilan' => ['Usia Kehamilan', 'minggu'],
        'pelayanan_ttd_mms' => ['Pelayanan TTD / MMS', null],
        'mt_bumil_kek' => ['Makanan Tambahan Bumil KEK', null],
        'pemberian_vitamin_a' => ['Pemberian Vitamin A', null],
        'status_menyusui' => ['Status Menyusui', null],
        'kb_pasca_persalinan' => ['KB Pasca Persalinan', null],
        'topik_penyuluhan' => ['Topik Penyuluhan', null],
        'rujukan' => ['Rujukan', null],
    ];

    $labelUntuk = function (string $kolom) use ($labelKolom) {
        if (isset($labelKolom[$kolom])) {
            return $labelKolom[$kolom];
        }
        return [\Illuminate\Support\Str::headline($kolom), null];
    };

    $formatNilai = function ($nilai) {
        if ($nilai === null || $nilai === '') return '—';
        if (is_bool($nilai)) return $nilai ? 'Ya' : 'Tidak';
        if (is_array($nilai)) return collect($nilai)->filter()->join(', ') ?: '—';
        return (string) $nilai;
    };
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

            {{-- Kartu per bagian --}}
            @php
                $bagianTampil = collect($definisi->bagian())
                    ->filter(fn ($b) => $b->berlakuUntuk($warga, $pemeriksaan->tanggal_pemeriksaan));
            @endphp

            @forelse ($bagianTampil as $bagian)
                @php $isi = $nilai[$bagian->kunci] ?? []; @endphp

                <div class="mt-4 card overflow-hidden">

                    {{-- Judul bagian --}}
                    <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-3.5">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">
                            {{ $bagian->judul }}
                        </h3>
                    </div>

                    {{-- Isi key-value --}}
                    <dl class="grid gap-x-6 gap-y-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($bagian->kolom() as $kolom)
                            @php
                                [$label, $satuan] = $labelUntuk($kolom);
                                $nilaiTampil = $formatNilai($isi[$kolom] ?? null);
                            @endphp

                            <div class="min-w-0">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                    {{ $label }}
                                </dt>
                                <dd @class([
                                        'mt-1 text-[14px] text-slate-800',
                                        'text-slate-400 italic' => $nilaiTampil === '—',
                                    ])>
                                    {{ $nilaiTampil }}
                                    @if ($satuan && $nilaiTampil !== '—')
                                        <span class="ml-1 text-[11px] font-medium text-slate-400">{{ $satuan }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @empty
                <div class="mt-6 rounded-xl border border-slate-200 bg-white px-4 py-10 text-center">
                    <p class="text-sm font-semibold text-slate-900">Tidak ada data pemeriksaan</p>
                    <p class="mt-1 text-sm text-slate-400">Belum ada hasil yang tercatat pada kunjungan ini.</p>
                </div>
            @endforelse
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