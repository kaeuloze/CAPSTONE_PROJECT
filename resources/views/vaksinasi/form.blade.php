@php
    $usiaBulan = $usiaBulan ?? 0;
@endphp


<x-layouts.app title="Vaksinasi Balita" active="vaksinasi">

    <div class="mx-auto max-w-4xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ route('vaksinasi.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke daftar balita
        </a>


        {{-- ═══════════ IDENTITAS BALITA ═══════════ --}}
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl
 border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-amber-100
 text-base font-bold uppercase text-amber-700">
                    {{ \Illuminate\Support\Str::substr($warga->nama_warga, 0, 2) }}
                </span>

                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $warga->nama_warga }}</h2>
                    <p class="mt-0.5 font-mono text-[12px] text-slate-500">{{ $warga->nik }}</p>
                    <p class="mt-1 text-[12px] font-medium text-slate-600">
                        Usia <strong class="text-amber-700">{{ $usiaBulan }} bulan</strong>
                        &middot; {{ $kegiatan->nama_kegiatan }}
                    </p>
                </div>
            </div>

            <x-ui.badge warna="amber">Balita</x-ui.badge>
        </div>


        {{-- ═══════════ ERROR ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    Data belum bisa disimpan. Periksa kembali isian yang ditandai merah:
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        <form method="POST" id="form-vaksinasi" class="mt-5 space-y-5" novalidate
              action="{{ route('vaksinasi.simpan', $warga->nik) }}"
              data-tanggal-kegiatan="{{ $kegiatan->tanggal_kegiatan->toDateString() }}">
            @csrf

            {{-- ═══════ IMUNISASI DASAR ═══════ --}}
            <div class="card overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">
                        Imunisasi Dasar (per dosis)
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Centang sesuai catatan di Buku KIA, lalu isi tanggal pemberiannya.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="th">Jenis Imunisasi (dosis)</th>
                                <th class="th w-40 text-center">Status</th>
                                <th class="th w-52">Tanggal (Buku KIA)</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftarVaksin as $v)
                                @php
                                    $tersimpan = $vaksinasi->get($v->id_vaksin);
                                    $sidik = \App\Services\VaksinasiService::sidikVaksin($tersimpan);
                                    $sudah    = (bool) old("vaksin.{$v->id_vaksin}.sudah", $tersimpan?->status_vaksinasi ? '1' : '0');
                                    $tanggal  = old("vaksin.{$v->id_vaksin}.tanggal", $tersimpan?->tanggal_vaksinasi?->toDateString());
                                    $galat    = $errors->first("vaksin.{$v->id_vaksin}.tanggal");
                                @endphp

                                {{-- HOOK [data-baris-vaksin] + [data-sudah-tersimpan] --}}
                                <tr data-baris-vaksin data-sudah-tersimpan="{{ $tersimpan?->status_vaksinasi ? '1' : '0' }}"
                                    class="transition hover:bg-mawar-50/30">

                                    <td class="td">
                                        <p class="font-semibold text-slate-900">{{ $v->nama_vaksin }}</p>
                                        @if ($v->deskripsi)
                                            <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">{{ $v->deskripsi }}</p>
                                        @endif

                                        <input type="hidden" name="awal[vaksin][{{ $v->id_vaksin }}]"
                                               value="{{ old("awal.vaksin.{$v->id_vaksin}", $sidik) }}">
                                    </td>

                                    <td class="td text-center">
                                        <label class="inline-flex cursor-pointer items-center justify-center gap-3">
                                            <input type="hidden" name="vaksin[{{ $v->id_vaksin }}][sudah]" value="0">
                                            <input type="checkbox" name="vaksin[{{ $v->id_vaksin }}][sudah]" value="1"
                                                @checked($sudah)
                                                class="h-5 w-5 rounded border-slate-300 text-emerald-600 transition
                                                        focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1
                                                        checked:bg-emerald-600 checked:border-emerald-600"
                                                data-hook-checkbox />

                                            {{-- HOOK [data-label-status] — JS mengganti teksnya --}}
                                            <span data-label-status
                                                class="text-[12px] font-bold {{ $sudah ? 'text-emerald-700' : 'text-slate-400' }}">
                                                {{ $sudah ? 'Sudah' : 'Belum' }}
                                            </span>
                                        </label>
                                    </td>

                                    <td @class(['td', 'galat-cell' => $galat])>
                                        <input type="date"
                                               name="vaksin[{{ $v->id_vaksin }}][tanggal]"
                                               value="{{ $tanggal }}"
                                               min="{{ $warga->tanggal_lahir->toDateString() }}"
                                               max="{{ $kegiatan->tanggal_kegiatan->toDateString() }}"
                                               aria-label="Tanggal {{ $v->nama_vaksin }}"
                                               @disabled(! $sudah)
                                               class="input py-1.5 text-[13px] disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />

                                        @if ($galat)
                                            <p class="mt-1 text-[11px] text-rose-600">{{ $galat }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="td text-center text-slate-400">
                                        Belum ada vaksin di Master Vaksin.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- ═══════ ASI EKSKLUSIF ═══════ --}}
            @if ($tampilAsi)
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">
                            ASI Eksklusif
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Status pemberian ASI selama6 bulan pertama.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-white">
                                <tr>
                                    <th class="th">Periode ASI Eksklusif</th>
                                    <th class="th w-64">Status (Ya / Tidak / Tidak Recall)</th>
                                    <th class="th w-40">Tanggal Dicatat</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($bulanAsi as $bulan)
                                    @php
                                        $tersimpan = $asi->get($bulan);
                                        $nilai = old("asi.{$bulan}", $tersimpan?->status_asi?->value);
                                    @endphp

                                    <tr class="transition hover:bg-mawar-50/30">
                                        <td class="td">
                                            <span class="font-semibold text-slate-900">Bulan {{ $bulan }}</span>
                                            <input type="hidden" name="awal[asi][{{ $bulan }}]"
                                                   value="{{ old("awal.asi.{$bulan}", \App\Services\VaksinasiService::sidikAsi($tersimpan)) }}">
                                        </td>

                                        <td class="td">
                                            <select name="asi[{{ $bulan }}]" aria-label="ASI Bulan {{ $bulan }}"
                                                    class="input py-1.5 text-[13px]">
                                                <option value="">Ya / Tidak / Tidak Recall</option>
                                                @foreach (\App\Enums\StatusAsi::options() as $kode => $label)
                                                    <option value="{{ $kode }}" @selected($nilai === $kode)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="td text-[13px] text-slate-500">
                                            {{ $tersimpan?->tanggal_dicatat?->translatedFormat('M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <p class="text-[13px] leading-relaxed text-slate-500">
                        ASI Eksklusif tidak ditampilkan karena usia balita sudah lewat 6 bulan.
                    </p>
                </div>
            @endif


            {{-- ═══════════ TOMBOL ═══════════ --}}
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Simpan
                </button>

                <a href="{{ route('vaksinasi.index') }}" class="btn btn-secondary py-2.5">Batal</a>
            </div>
        </form>


        {{-- ═══════════ INFO ═══════════ --}}
        <p class="mt-5 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3
                  text-[12px] leading-relaxed text-slate-500">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <span>
                Centang dosis sesuai catatan di <strong>Buku KIA</strong>, lalu isi tanggal pemberiannya
                (bawaan: tanggal kegiatan). Hapus centang bila salah mencentang. Catatan tersimpan
                juga tampil di <strong>Riwayat Pemeriksaan</strong>.
            </span>
        </p>
    </div>


    {{-- ═══════════ JS ═══════════ --}}
    <script src="{{ asset('js/vaksinasi-form.js') }}" defer></script>

</x-layouts.app>