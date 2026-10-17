{{--NOTE: data masih dummy. Nanti diisi dari tabel `kader`.--}}
@php
    $ref = include resource_path('views/data/reference.php');
@endphp


<x-layouts.app title="Manajemen Pengguna" active="pengguna"
 x-data="{
                  modal: false,
                  mode: 'tambah',
                  showSandi: false,
                  galat: {},
                  form: { nama_kader: '', username: '', password_hash: '', konfirmasi: '' },

                  bukaTambah() {
                      this.mode = 'tambah';
                      this.galat = {};
                      this.form = { nama_kader: '', username: '', password_hash: '', konfirmasi: '' };
                      this.modal = true;
                  },

                  bukaEdit(baris) {
                      this.mode = 'edit';
                      this.galat = {};
                      this.form = { ...baris, password_hash: '', konfirmasi: '' };
                      this.modal = true;
                  },

                  validasi() {
                      this.galat = {};
                      if (! this.form.nama_kader.trim())                    this.galat.nama_kader = 'Nama kader wajib diisi.';
                      if (! this.form.username.trim())                      this.galat.username   = 'Username wajib diisi.';
                      if (! this.form.password_hash) this.galat.sandi      = 'Kata sandi wajib diisi.';
                      if (this.form.password_hash !== this.form.konfirmasi) this.galat.konfirmasi = 'Konfirmasi kata sandi tidak sama.';

                      return Object.keys(this.galat).length === 0;
                  },

                  simpan() {
                      if (! this.validasi()) return;
                      this.modal = false;
                  }
              }">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Kelola Data Akun</h2>
            <p class="mt-1 text-sm text-slate-500">
                Daftar kader / petugas yang dapat masuk ke sistem. Mendukung beberapa kader
                bekerja bersamaan dari perangkat berbeda.
            </p>
        </div>

        <button type="button" @click="bukaTambah()" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Akun
        </button>
    </div>

    {{-- ═══════════ TABEL AKUN ═══════════ --}}
    <div class="card overflow-hidden">

        {{-- Pencarian --}}
        <div class="border-b border-slate-200 bg-slate-50/70 p-4">
            <div class="relative max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z" />
                </svg>
                <input type="search" placeholder="Cari nama kader atau username..."
                       class="input pl-9 py-2 text-[13px]" />
            </div>
        </div>

        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-white">
                <tr>
                    <th class="th">No</th>
                    <th class="th">Nama Kader</th>
                    <th class="th">Username</th>
                    <th class="th">Dibuat</th>
                    <th class="th">Kata Sandi</th>
                    <th class="th text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @foreach ($ref['kader'] as $i => $k)
                    <tr class="transition hover:bg-mawar-50/40">

                        <td class="td text-slate-400">{{ $i + 1 }}</td>

                        <td class="td">
                            <div class="flex items-center gap-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full
 bg-mawar-100 text-[11px] font-bold uppercase text-mawar-700">
                                    {{ substr($k['nama_kader'], 0, 2) }}
                                </span>
                                <span class="font-medium text-slate-900">{{ $k['nama_kader'] }}</span>
                            </div>
                        </td>

                        <td class="td">
                            <code class="rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">
                                {{ $k['username'] }}
                            </code>
                        </td>

                        <td class="td text-[13px] text-slate-500">{{ $k['created_at'] }}</td>

                        <td class="td">
                            @if ($k['sandi_diisi'])
                                <x-ui.badge warna="emerald">Diisi</x-ui.badge>
                            @else
                                <x-ui.badge warna="amber">Belum diisi</x-ui.badge>
                            @endif
                        </td>

                        <td class="td">
                            <div class="flex justify-end gap-1">

                                {{-- Edit --}}
                                <button type="button"
                                        @click='bukaEdit(@json([
    "nama_kader" => $k["nama_kader"],
    "username"   => $k["username"],
]))'
                                        title="Edit akun"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>

                                {{-- Hapus --}}
                                <button type="button"
                                        @click="$dispatch('konfirmasi-hapus', {
    judul: 'Yakin Hapus Akun Kader?',
    pesan: 'Akun {{ $k['nama_kader'] }} (@{{ $k['username'] }}) tidak dapat masuk lagi setelah dihapus.',
    aksi: 'Ya, Hapus'
})"
                                        title="Hapus akun"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
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


    {{-- ══════MODAL: FORM TAMBAH / EDIT AKUN══════ --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true">

        {{-- Backdrop --}}
        <div x-show="modal" x-transition.opacity @click="modal = false"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        {{-- Isi modal --}}
        <div x-show="modal" x-transition.scale.origin.center
             class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900"
                        x-text="mode === 'tambah' ? 'Tambah Akun Kader' : 'Edit Akun Kader'"></h3>
                    <p class="mt-0.5 text-xs text-slate-500">Kata sandi diisi oleh kader sendiri.</p>
                </div>
                <button type="button" @click="modal = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="simpan()" class="space-y-4 px-6 py-5">

                {{-- Nama Kader --}}
                <div>
                    <label for="f-nama" class="label">Nama Kader <span class="text-rose-500">*</span></label>
                    <input id="f-nama" type="text" x-model="form.nama_kader"
                           @input="delete galat.nama_kader"
                           class="input py-2" :class="galat.nama_kader && '!ring-rose-400'"
                           placeholder="Contoh: Ibu Yuli" />
                    <p x-show="galat.nama_kader" x-cloak x-text="galat.nama_kader"
                       class="mt-1 text-[11px] text-rose-600"></p>
                </div>

                {{-- Username --}}
                <div>
                    <label for="f-user" class="label">Username <span class="text-rose-500">*</span></label>
                    <input id="f-user" type="text" x-model="form.username"
                           @input="delete galat.username"
                           class="input py-2" :class="galat.username && '!ring-rose-400'"
                           placeholder="Contoh: yuli" />
                    <p x-show="galat.username" x-cloak x-text="galat.username"
                       class="mt-1 text-[11px] text-rose-600"></p>
                </div>

                {{-- Kata Sandi --}}
                <div>
                    <label for="f-sandi" class="label">Kata Sandi <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input id="f-sandi" :type="showSandi ? 'text' : 'password'" x-model="form.password_hash"
                               @input="delete galat.sandi"
                               class="input py-2 pr-10" :class="galat.sandi && '!ring-rose-400'"
                               placeholder="Minimal 6 karakter" autocomplete="new-password" />

                        <button type="button" @click="showSandi = !showSandi"
                                class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg p-1.5
 text-slate-400 transition hover:bg-mawar-50 hover:text-mawar-700">
                            <svg x-show="!showSandi" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="showSandi" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <p x-show="galat.sandi" x-cloak x-text="galat.sandi"
                       class="mt-1 text-[11px] text-rose-600"></p>
                </div>

                {{-- Konfirmasi --}}
                <div>
                    <label for="f-konfirmasi" class="label">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                    <input id="f-konfirmasi" :type="showSandi ? 'text' : 'password'" x-model="form.konfirmasi"
                           @input="delete galat.konfirmasi"
                           class="input py-2" :class="galat.konfirmasi && '!ring-rose-400'"
                           placeholder="Ulangi kata sandi" autocomplete="new-password" />
                    <p x-show="galat.konfirmasi" x-cloak x-text="galat.konfirmasi"
                       class="mt-1 text-[11px] text-rose-600"></p>
                </div>

                {{-- Tombol --}}
                <div class="flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                    <button type="button" @click="modal = false" class="btn btn-secondary py-2 text-[13px]">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary py-2 text-[13px]">
                        <span x-text="mode === 'tambah' ? 'Simpan Akun' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>