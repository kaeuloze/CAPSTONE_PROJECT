<x-layouts.app title="Pemeriksaan" active="pemeriksaan">

    <div class="space-y-6">

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Daftar Pemeriksaan</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Hanya warga yang berstatus <strong>Hadir</strong> pada kegiatan aktif yang ditampilkan.
                    </p>
                </div>
                <x-ui.badge warna="emerald">Draft</x-ui.badge>
            </div>
        </div>

        {{-- EMPTY STATE (Gambar 2.54) --}}
        <x-empty-state
            icon="clipboard"
            judul="Belum ada kegiatan aktif"
            deskripsi="Daftar warga yang dapat diperiksa terikat pada presensi kegiatan yang sedang berlangsung. Selesaikan atau buat kegiatan terlebih dahulu."
            :cta="['label' => 'Kelola Kegiatan', 'url' => '#']"
        />

    </div>

</x-layouts.app>