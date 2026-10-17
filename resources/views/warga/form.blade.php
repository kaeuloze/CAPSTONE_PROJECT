{{-- Tambah / Edit Warga (wireframe Gambar 2.44; Activity Diagram 2.30 & 2.31). --}}
@extends('layouts.app')

@php
    $modeEdit = $warga->exists;
    $statusKondisi = old('status_kondisi', $warga->status_kondisi?->value ?? 'normal');
    $jenisKelamin = old('jenis_kelamin', $modeEdit ? ($warga->jenis_kelamin ? '1' : '0') : '');
    $penyakitTercentang = array_map('intval', (array) old('penyakit', $penyakitDipilih));
@endphp

@section('judul', $modeEdit ? 'Edit Data Warga' : 'Tambah Warga Baru')
@section('subjudul', 'Kategori usia terdeteksi otomatis dari tanggal lahir')
@section('aksi-kepala')
    <a href="{{ route('warga.index') }}" class="tombol">← Kembali</a>
@endsection

@section('isi')
    @if ($errors->any())
        <div class="pesan galat" role="alert">
            Data belum bisa disimpan. Periksa kembali isian berikut:
            <ul>@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $modeEdit ? route('warga.update', $warga) : route('warga.store') }}"
          data-memuat="Menyimpan..." id="form-warga" novalidate>
        @csrf
        @if ($modeEdit)
            @method('PUT')
            {{-- Penanda versi data saat formulir dibuka (pengaman edit bersamaan antar-kader). --}}
            <input type="hidden" name="versi" value="{{ old('versi', $warga->updated_at?->format('Y-m-d H:i:s')) }}">
        @endif

        <section class="kartu">
            <h2>Identitas Warga</h2>
            <div class="kisi">
                <div @class(['salah' => $errors->has('nama_warga')])>
                    <label for="nama_warga">Nama lengkap</label>
                    <input type="text" id="nama_warga" name="nama_warga" value="{{ old('nama_warga', $warga->nama_warga) }}" maxlength="100" required autocomplete="off">
                    @error('nama_warga')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('nik')])>
                    <label for="nik">NIK</label>
                    <input type="text" id="nik" name="nik" value="{{ old('nik', $warga->nik) }}" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" required autocomplete="off">
                    @error('nik')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('tanggal_lahir')])>
                    <label for="tanggal_lahir">Tanggal lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $warga->tanggal_lahir?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" min="1900-01-02" required>
                    @error('tanggal_lahir')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('no_hp')])>
                    <label for="no_hp">Nomor HP (opsional)</label>
                    <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $warga->no_hp) }}" inputmode="tel" maxlength="20" autocomplete="off">
                    @error('no_hp')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div @class(['salah' => $errors->has('jenis_kelamin')])>
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="" @selected($jenisKelamin === '')>Pilih jenis kelamin</option>
                        <option value="1" @selected($jenisKelamin === '1')>Laki-laki</option>
                        <option value="0" @selected($jenisKelamin === '0')>Perempuan</option>
                    </select>
                    @error('jenis_kelamin')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
                <div class="penuh @error('alamat') salah @enderror">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat" value="{{ old('alamat', $warga->alamat) }}" maxlength="255" required autocomplete="off">
                    @error('alamat')<div class="galat-isian">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="info">Badge kategori usia muncul otomatis dari tanggal lahir: Balita / Remaja / Dewasa / Lansia / Ibu Hamil.</div>
            <p style="font-size:12px;color:#4b5563;margin:16px 0 0">Kategori usia (otomatis dari tgl lahir):
                <span class="badge" id="badge-kategori">{{ $warga->tanggal_lahir ? $warga->kategori_usia_terkini->label() : 'Kategori' }}</span></p>
        </section>

        <section class="kartu">
            <h2>Status Kondisi Khusus (manual)</h2>
            <p class="ket">Untuk ibu hamil / nifas / menyusui tidak bisa dideteksi dari tanggal lahir, jadi ditandai manual oleh kader. Hanya untuk warga perempuan yang bukan balita.</p>
            <div class="kotak-status">
                <span class="nilai" id="status-saat-ini"></span>
                <button type="button" class="tombol abu" id="tombol-tandai">+ Tandai Ibu Hamil / Nifas</button>
            </div>
            <div id="pilihan-kondisi" style="margin-top:16px;max-width:320px" @class(['salah' => $errors->has('status_kondisi')])>
                <label for="status_kondisi">Kondisi khusus</label>
                <select id="status_kondisi" name="status_kondisi">
                    @foreach (\App\Enums\StatusKondisi::options() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($statusKondisi === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status_kondisi')<div class="galat-isian">{{ $message }}</div>@enderror
            </div>
        </section>

        <section class="kartu">
            <h2>Riwayat Penyakit Tidak Menular (PTM)</h2>
            <p class="ket">Dicatat sekali saat warga pertama kali terdaftar. Centang penyakit yang pernah/sedang dialami warga maupun yang ada pada riwayat keluarga. Data ini menjadi sumber Grafik Risiko PTM di Dashboard.</p>
            @foreach ($daftarPenyakit as $penyakit)
                <label class="centang">
                    <input type="checkbox" name="penyakit[]" value="{{ $penyakit->id_penyakit }}" @checked(in_array($penyakit->id_penyakit, $penyakitTercentang, true))>
                    {{ $penyakit->nama_penyakit }}
                </label>
            @endforeach
            @error('penyakit')<div class="galat-isian">{{ $message }}</div>@enderror
            @error('penyakit.*')<div class="galat-isian">{{ $message }}</div>@enderror
        </section>

        <div class="aksi-form">
            <button type="submit" class="tombol abu">{{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Warga' }}</button>
            <a href="{{ route('warga.index') }}" class="tombol">Batal</a>
        </div>
    </form>

    <div class="info">Kategori usia (Balita→Remaja→Dewasa→Lansia) selalu otomatis dari tanggal lahir. Status hamil/nifas/menyusui adalah penanda sementara manual.</div>
@endsection

@push('skrip')
<script>
    // Pratinjau kategori usia & tombol "Tandai Ibu Hamil / Nifas" (keputusan akhir tetap dicek backend).
    (function () {
        var batas = { remaja: {{ (int) config('posyandu.kategori_usia.remaja_mulai') }}, dewasa: {{ (int) config('posyandu.kategori_usia.dewasa_mulai') }}, lansia: {{ (int) config('posyandu.kategori_usia.lansia_mulai') }} };
        var label = @json(\App\Enums\KategoriUsia::options());
        var labelKondisi = @json(\App\Enums\StatusKondisi::options());
        var tgl = document.getElementById('tanggal_lahir');
        var jk = document.getElementById('jenis_kelamin');
        var badge = document.getElementById('badge-kategori');
        var statusSaatIni = document.getElementById('status-saat-ini');
        var tombolTandai = document.getElementById('tombol-tandai');
        var pilihan = document.getElementById('pilihan-kondisi');
        var kondisi = document.getElementById('status_kondisi');

        function kategori() {
            if (!tgl.value) { return null; }
            var lahir = new Date(tgl.value + 'T00:00:00'), kini = new Date();
            var usia = kini.getFullYear() - lahir.getFullYear();
            if (kini.getMonth() < lahir.getMonth() || (kini.getMonth() === lahir.getMonth() && kini.getDate() < lahir.getDate())) { usia--; }
            if (usia < 0) { return null; }
            if (usia >= batas.lansia) { return 'lansia'; }
            if (usia >= batas.dewasa) { return 'dewasa'; }
            if (usia >= batas.remaja) { return 'remaja'; }
            return 'balita';
        }

        function perbarui() {
            var k = kategori();
            badge.textContent = k ? label[k] : 'Kategori';
            var boleh = jk.value === '0' && k !== null && k !== 'balita';
            if (!boleh && kondisi.value !== 'normal') { kondisi.value = 'normal'; }
            tombolTandai.hidden = !boleh || kondisi.value !== 'normal';
            pilihan.hidden = !boleh || (kondisi.value === 'normal' && !pilihan.dataset.dibuka);
            statusSaatIni.textContent = 'Status saat ini: ' + (k ? label[k] : '-') + ' ('
                + (kondisi.value === 'normal' ? 'tidak ada kondisi khusus' : labelKondisi[kondisi.value]) + ')';
        }

        tombolTandai.addEventListener('click', function () {
            pilihan.dataset.dibuka = '1';
            kondisi.value = 'hamil';
            perbarui();
            kondisi.focus();
        });
        [tgl, jk, kondisi].forEach(function (el) { el.addEventListener('change', perbarui); });
        perbarui();
    })();
</script>
@endpush
