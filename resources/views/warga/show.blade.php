@php
    $warnaKategori = ['balita' => 'amber', 'remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet'];
    $kategori = $warga->kategori_usia_terkini;
    $kondisi  = $warga->status_kondisi;
@endphp


<x-layouts.app title="Detail Warga" active="warga">

    <div class="mx-auto max-w-4xl">

        {{-- ═══════════ HEADER + PROFIL ═══════════ --}}
        <a href="{{ route('warga.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar warga
        </a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <span @class([
                    'grid h-16 w-16 shrink-0 place-items-center rounded-2xl text-lg font-bold uppercase',
                    'bg-rose-100 text-rose-700'   => $warga->jenis_kelamin === 'perempuan',
                    'bg-mawar-100 text-mawar-700' => $warga->jenis_kelamin !== 'perempuan',
                ])>
                    {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
                </span>

                <div class="min-w-0">
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $warga->nama_warga }}</h2>
                    <p class="mt-1 font-mono text-[13px] text-slate-500">{{ $warga->nik }}</p>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <x-ui.badge :warna="$warnaKategori[$kategori->value] ?? 'mawar'">
                            {{ $kategori->label() }}
                        </x-ui.badge>

                        @if ($kondisi && $kondisi->value !== 'normal')
                            <x-ui.badge warna="rose">{{ $kondisi->label() }}</x-ui.badge>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('warga.edit', $warga->nik) }}" class="btn btn-primary py-2 text-[13px]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Edit
                </a>

                <a href="{{ route('riwayat.warga', $warga->nik) }}" class="btn btn-secondary py-2 text-[13px]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Riwayat Pemeriksaan
                </a>

                <button type="button"
                        @click="$dispatch('konfirmasi-hapus', {
    judul: 'Yakin hapus data warga?',
    pesan: 'Data {{ $warga->nama_warga }} beserta seluruh riwayat pemeriksaan akan dihapus permanen.',
    aksi: 'Ya, Hapus'
})"
                        class="btn bg-rose-600 py-2 text-[13px] text-white shadow-sm hover:bg-rose-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916" />
                    </svg>
                    Hapus
                </button>
            </div>
        </div>


        {{-- ═══════════ IDENTITAS ═══════════ --}}
        <div class="mt-6 card p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Identitas Warga</h3>

            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['Nama Lengkap',   $warga->nama_warga],
                    ['NIK',            $warga->nik, true],
                    ['Tanggal Lahir',  $warga->tanggal_lahir->translatedFormat('d F Y')],
                    ['Nomor HP',       $warga->no_hp ?: '-'],
                    ['Jenis Kelamin',  $warga->jenis_kelamin ? 'Laki-laki' : 'Perempuan'],
                    ['Kategori Usia',  $kategori->label() . ' (otomatis)'],
                ] as [$label, $isi, $mono ?? false])
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                        <dd @class([
                            'mt-1 text-[14px] text-slate-800',
                            'font-mono text-[13px]' => $mono,
                        ])>{{ $isi }}</dd>
                    </div>
                @endforeach

                <div class="sm:col-span-2">
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Alamat</dt>
                    <dd class="mt-1 text-[14px] leading-relaxed text-slate-800">{{ $warga->alamat }}</dd>
                </div>
            </dl>
        </div>


        {{-- ═══════════ STATUS KONDISI KHUSUS ═══════════ --}}
        <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50/50 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-rose-800">Status Kondisi Khusus</h3>
            <p class="mt-1.5 text-[13px] leading-relaxed text-rose-900/75">
                Untuk ibu hamil / nifas / menyusui tidak bisa dideteksi dari tanggal lahir,
                jadi ditandai manual oleh kader.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="rounded-xl bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-800 ring-1 ring-rose-200">
                    Status saat ini:
                    <strong class="text-mawar-800">{{ $kategori->label() }}</strong>
                    ({{ ($kondisi && $kondisi->value !== 'normal') ? $kondisi->label() : 'tidak ada kondisi khusus' }})
                </span>

                @if ($kondisi && $kondisi->value !== 'normal')
                    <x-ui.badge warna="rose">{{ $kondisi->label() }}</x-ui.badge>
                @endif
            </div>
        </div>


        {{-- ═══════════ RIWAYAT PTM ═══════════ --}}
        <div class="mt-5 card p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Riwayat Penyakit Tidak Menular</h3>
            <p class="mt-1.5 text-[13px] leading-relaxed text-slate-500">
                Dicatat sekali saat warga pertama kali terdaftar. Data ini menjadi sumber
                <strong>Grafik Risiko PTM</strong> di Dashboard.
            </p>

            @if ($daftarPenyakit->isEmpty())
                <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-[13px] text-slate-500">
                    Belum ada data master penyakit.
                </p>
            @else
                @php $terpilih = array_map('intval', $penyakitDipilih ?? []); @endphp

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($daftarPenyakit as $penyakit)
                        @php $dipilih = in_array($penyakit->id_penyakit, $terpilih, true); @endphp

                        <span @class([
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[12px] font-semibold',
                            'bg-rose-100 text-rose-700 ring-1 ring-rose-200'   => $dipilih,
                            'bg-slate-50 text-slate-400 ring-1 ring-slate-200' => ! $dipilih,
                        ])>
                            @if ($dipilih)
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @else
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                            @endif
                            {{ $penyakit->nama_penyakit }}
                        </span>
                    @endforeach
                </div>

                <p class="mt-4 text-[12px] text-slate-500">
                    Terpilih<strong class="text-rose-700">{{ count($terpilih) }}</strong>
                    dari {{ $daftarPenyakit->count() }} penyakit.
                </p>
            @endif
        </div>


        {{-- ═══════════ INFO ═══════════ --}}
        <p class="mt-5 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
 text-[12px] leading-relaxed text-slate-500">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <span>
                Kategori usia (Balita → Remaja → Dewasa → Lansia) selalu otomatis dari tanggal lahir.
                Status hamil/nifas/menyusui adalah penanda sementara manual.
            </span>
        </p>
    </div>

</x-layouts.app>