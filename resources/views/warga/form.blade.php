@php
    $modeEdit  = $warga->exists;
    $jenisKelamin = old('jenis_kelamin', $modeEdit ? ($warga->jenis_kelamin ? '1' : '0') : '');
    $tercentang = array_map('intval', (array) old('penyakit', $penyakitDipilih ?? []));

    $warnaKategori = ['balita' => 'amber', 'remaja' => 'sky', 'dewasa' => 'mawar', 'lansia' => 'violet'];
     $kategoriNow  = $warga->tanggal_lahir
        ? $warga->kategori_usia_terkini
        : \App\Enums\KategoriUsia::Dewasa;
    $kondisiNow   = $warga->status_kondisi;
@endphp


<x-layouts.app :title="$modeEdit ? 'Edit Data Warga' : 'Tambah Warga Baru'" active="warga">

    <div class="mx-auto max-w-3xl">

        {{-- ═══════════ KEMBALI ═══════════ --}}
        <a href="{{ $modeEdit ? route('warga.show', $warga->nik) : route('warga.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-mawar-800">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            {{ $modeEdit ? 'Kembali ke detail' : 'Kembali ke daftar warga' }}
        </a>

        <h2 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">
            {{ $modeEdit ? 'Edit Data Warga' : 'Tambah Warga Baru' }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Kategori usia terdeteksi otomatis dari tanggal lahir — tidak perlu diisi manual.
        </p>


        {{-- ═══════════ ERROR ═══════════ --}}
        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="text-sm font-medium text-rose-900">
                    Data belum bisa disimpan. Periksa kembali isian berikut:
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[13px]">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        {{-- ═══════════ FORM ═══════════ --}}
        <form method="POST" id="form-warga" class="mt-6 space-y-5" novalidate
              action="{{ $modeEdit ? route('warga.update', $warga->nik) : route('warga.store') }}">
            @csrf
            @if ($modeEdit) @method('PUT') @endif

            {{-- Penanda versi (pengaman edit antar-kader) --}}
            @if ($modeEdit)
                <input type="hidden" name="versi"
                       value="{{ old('versi', $warga->updated_at?->format('Y-m-d H:i:s')) }}">
            @endif


            {{-- ═══════ IDENTITAS ═══════ --}}
            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Identitas Warga</h3>

                {{-- Nama --}}
                <div class="mt-5">
                    <label for="nama_warga" class="label">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input id="nama_warga" name="nama_warga" type="text" required maxlength="100"
                           autocomplete="off" value="{{ old('nama_warga', $warga->nama_warga) }}"
                           placeholder="Sesuai KTP / KK"
                           class="input py-2.5 @error('nama_warga') !ring-rose-400 @enderror" />
                    @error('nama_warga') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- NIK --}}
                <div class="mt-4">
                    <label for="nik" class="label">NIK <span class="text-rose-500">*</span></label>
                    <input id="nik" name="nik" type="text" required maxlength="16" inputmode="numeric"
                           pattern="[0-9]{16}" autocomplete="off"
                           value="{{ old('nik', $warga->nik) }}"
                           placeholder="16 digit angka sesuai KTP"
                           class="input py-2.5 font-mono @error('nik') !ring-rose-400 @enderror" />
                    @error('nik') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tanggal lahir + JK --}}
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tanggal_lahir" class="label">Tanggal Lahir <span class="text-rose-500">*</span></label>
                        <input id="tanggal_lahir" name="tanggal_lahir" type="date" required
                               min="1900-01-02" max="{{ now()->toDateString() }}"
                               value="{{ old('tanggal_lahir', $warga->tanggal_lahir?->format('Y-m-d')) }}"
                               class="input py-2.5 @error('tanggal_lahir') !ring-rose-400 @enderror" />
                        @error('tanggal_lahir') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="jenis_kelamin" class="label">Jenis Kelamin <span class="text-rose-500">*</span></label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required
                                class="input py-2.5 @error('jenis_kelamin') !ring-rose-400 @enderror">
                            <option value="">-- Pilih --</option>
                            <option value="0" @selected($jenisKelamin === '0')>Perempuan</option>
                            <option value="1" @selected($jenisKelamin === '1')>Laki-laki</option>
                        </select>
                        @error('jenis_kelamin') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- No HP --}}
                <div class="mt-4">
                    <label for="no_hp" class="label">Nomor HP <span class="text-xs font-normal text-slate-400">(opsional)</span></label>
                    <input id="no_hp" name="no_hp" type="text" maxlength="20" inputmode="tel" autocomplete="off"
                           value="{{ old('no_hp', $warga->no_hp) }}"
                           placeholder="081234567890"
                           class="input py-2.5 @error('no_hp') !ring-rose-400 @enderror" />
                    @error('no_hp') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Alamat --}}
                <div class="mt-4">
                    <label for="alamat" class="label">Alamat <span class="text-rose-500">*</span></label>
                    <textarea id="alamat" name="alamat" rows="3" required maxlength="255" autocomplete="off"
 placeholder="Contoh: Tegalyoso RT 02 / RW 05"
                              class="input py-2.5 @error('alamat') !ring-rose-400 @enderror">{{ old('alamat', $warga->alamat) }}</textarea>
                    @error('alamat') <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>


            {{-- ═══════ KATEGORI OTOMATIS ═══════ --}}
            <div class="rounded-2xl border border-mawar-200 bg-mawar-50/60 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-mawar-700">
                            Kategori Usia (Otomatis)
                        </p>
                        <p id="status-saat-ini" class="mt-1 text-[13px] font-medium text-mawar-900">
                            Status saat ini: {{ $kategoriNow->label() }}
                            ({{ ($kondisiNow && $kondisiNow->value !== 'normal') ? $kondisiNow->label() : 'tidak ada kondisi khusus' }})
                        </p>
                    </div>

                    <x-ui.badge :warna="$warnaKategori[$kategoriNow->value] ?? 'mawar'"
 id="badge-kategori"
                               class="!px-3 !py-1.5 !text-sm">
                        {{ $kategoriNow->label() }}
                    </x-ui.badge>
                </div>

                <p class="mt-3 border-t border-mawar-200/60 pt-3 text-[11px] leading-relaxed text-mawar-800/80">
                    Badge kategori usia muncul otomatis dari tanggal lahir                    (Balita → Remaja → Dewasa → Lansia). Status hamil/nifas/menyusui
                    adalah penanda sementara yang ditandai manual oleh kader.
                </p>
            </div>


            {{-- ═══════ STATUS KONDISI KHUSUS ═══════ --}}
            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Status Kondisi Khusus</h3>
                <p class="mt-1.5 text-[13px] leading-relaxed text-slate-500">
                    Untuk ibu hamil / nifas / menyusui tidak bisa dideteksi dari tanggal lahir,
                    jadi ditandai manual oleh kader. Hanya untuk warga perempuan bukan balita.
                </p>

                {{-- Tombol tandai (HOOK #tombol-tandai — tanpa class display) --}}
                <div id="tombol-tandai" class="mt-4" hidden>
                    <button type="button"
                            class="rounded-xl border-2 border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px]
 font-bold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                        Tandai Ibu Hamil / Nifas
                    </button>
                </div>

                {{-- Pilihan kondisi (HOOK #pilihan-kondisi — tanpa class display) --}}
                <div id="pilihan-kondisi" class="mt-4" hidden>
                    <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-4">
                        <label for="status_kondisi" class="label">Status Kondisi</label>
                        <select id="status_kondisi" name="status_kondisi"
                                class="input py-2.5 @error('status_kondisi') !ring-rose-400 @enderror">
                            @foreach (\App\Enums\StatusKondisi::options() as $nilai => $labelKondisi)
                                <option value="{{ $nilai }}"
                                        @selected(old('status_kondisi', $kondisiNow?->value ?? 'normal') === $nilai)>
                                    {{ $labelKondisi }}
                                </option>
                            @endforeach
                        </select>
                        @error('status_kondisi')
                            <p class="mt-1.5 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Select selalu ada (hidden) supaya old() & backend tetap terima nilainya --}}
                <input type="hidden" name="status_kondisi"
                    id="status_kondisi_hidden"
                value="{{ old('status_kondisi', $kondisiNow?->value ?? 'normal') }}">

            </div>


            {{-- ═══════ RIWAYAT PTM ═══════ --}}
            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">Riwayat Penyakit Tidak Menular</h3>
                <p class="mt-1.5 text-[13px] leading-relaxed text-slate-500">
                    Dicatat sekali saat warga pertama kali terdaftar. Centang penyakit yang pernah/dialami
                    warga maupun yang ada pada riwayat keluarga. Data ini menjadi sumber
                    <strong>Grafik Risiko PTM</strong> di Dashboard.
                </p>

                @if ($daftarPenyakit->isEmpty())
                    <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-[13px] text-slate-500">
                        Belum ada data master penyakit.
                    </p>
                @else
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($daftarPenyakit as $penyakit)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200
                                          px-3.5 py-2.5 transition hover:border-mawar-300 hover:bg-mawar-50/50">
                                <input type="checkbox" name="penyakit[]" value="{{ $penyakit->id_penyakit }}"
                                       @checked(in_array($penyakit->id_penyakit, $tercentang, true))
                                       class="h-4 w-4 rounded border-slate-300 text-mawar-600 focus:ring-mawar-500">
                                <span class="text-[13px] font-medium text-slate-700">{{ $penyakit->nama_penyakit }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @error('penyakit') <p class="mt-2 text-[11px] text-rose-600">{{ $message }}</p> @enderror
            </div>


            {{-- ═══════════ TOMBOL ═══════════ --}}
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary py-2.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Warga' }}
                </button>

                <a href="{{ $modeEdit ? route('warga.show', $warga->nik) : route('warga.index') }}"
                   class="btn btn-secondary py-2.5">Batal</a>
            </div>
        </form>
    </div>

    {{-- ═══════════ JS PRATINJAU KATEGORI ═══════════ --}}
    <script>
        (function () {
            var batas = {
                remaja: {{ (int) config('posyandu.kategori_usia.remaja_mulai') }},
                dewasa: {{ (int) config('posyandu.kategori_usia.dewasa_mulai') }},
                lansia: {{ (int) config('posyandu.kategori_usia.lansia_mulai') }}
            };
            var label = @json(\App\Enums\KategoriUsia::options());
            var labelKondisi = @json(\App\Enums\StatusKondisi::options());
            var warna        = { balita: 'amber', remaja: 'sky', dewasa: 'mawar', lansia: 'violet' };

            var tgl     = document.getElementById('tanggal_lahir');
            var jk      = document.getElementById('jenis_kelamin');
            var badge   = document.getElementById('badge-kategori');
            var status  = document.getElementById('status-saat-ini');
            var tombol  = document.getElementById('tombol-tandai');
            var pilihan = document.getElementById('pilihan-kondisi');
            var kondisi = document.getElementById('status_kondisi');
            var kondisiHidden = document.getElementById('status_kondisi_hidden');

            if (!tgl || !jk || !kondisi) { return; }

            function kategori() {
                if (!tgl.value) { return null; }
                var lahir = new Date(tgl.value + 'T00:00:00'), kini = new Date();
                var usia = kini.getFullYear() - lahir.getFullYear();
                if (kini.getMonth() < lahir.getMonth() ||
                    (kini.getMonth() === lahir.getMonth() && kini.getDate() < lahir.getDate())) { usia--; }
                if (usia < 0) { return null; }
                if (usia >= batas.lansia) { return 'lansia'; }
                if (usia >= batas.dewasa) { return 'dewasa'; }
                if (usia >= batas.remaja) { return 'remaja'; }
                return 'balita';
            }

            function perbarui() {
                var k = kategori();

                if (badge) { badge.textContent = k ? label[k] : 'Kategori'; }

                // Hanya perempuan & bukan balita yang boleh punya kondisi khusus.
                var boleh = jk.value === '0' && k !== null && k !== 'balita';
                var nilai = (boleh && kondisi.value !== 'normal') ? kondisi.value : 'normal';

                if (kondisiHidden) { kondisiHidden.value = nilai; }
                if (status) {
                    status.textContent = 'Status saat ini: ' + (k ? label[k] : '-') + ' (' +
                        (nilai === 'normal' ? 'tidak ada kondisi khusus' : labelKondisi[nilai]) + ')';
                }

                if (tombol)  { tombol.hidden  = !boleh || nilai !== 'normal'; }
                if (pilihan) { pilihan.hidden = !boleh || (nilai === 'normal' && !pilihan.dataset.dibuka); }
            }

            if (tombol) {
                tombol.addEventListener('click', function () {
                    if (!pilihan) { return; }
                    pilihan.dataset.dibuka = '1';
                    kondisi.value = 'hamil';
                    perbarui();
                    kondisi.focus();
                });
            }

            [tgl, jk, kondisi].forEach(function (el) { el.addEventListener('change', perbarui); });
            perbarui();
        })();
    </script>

</x-layouts.app>