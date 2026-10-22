{{--
    Kartu identitas warga. Variabel: $warga, $kategori, $usia.
    Opsional: $keterangan (teks di bawah kartu; bawaan = keterangan kehadiran).
--}}

<section class="identitas-periksa">
    <span class="avatar" aria-hidden="true">
        {{ \Illuminate\Support\Str::substr($warga->nama_warga ?? '?', 0, 2) }}
    </span>

    <div class="min-w-0">
        <p class="font-bold text-slate-900">{{ $warga->nama_warga }}</p>
        <p class="mt-0.5 font-mono text-[12px] text-slate-400">NIK {{ $warga->nik }}</p>
    </div>

    <x-ui.badge warna="mawar" class="ml-auto">
        {{ $kategori->label() }} &middot; {{ $usia }} th
    </x-ui.badge>
</section>

<p class="info mt-3 mb-5">
    {{ $keterangan ?? 'Kehadiran: Hadir (tercatat dari presensi kegiatan)' }}
</p>