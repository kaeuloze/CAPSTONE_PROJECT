<x-layouts.app title="Kelola Kegiatan" active="kegiatan">

    <div class="space-y-6">

        {{-- Tab --}}
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6">
                <a href="#" class="border-b-2 border-emerald-600 px-1 pb-3 text-sm font-semibold text-emerald-600">
                    Kegiatan Berlangsung
                </a>
                <a href="#" class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 hover:border-slate-300">
                    Arsip Kegiatan
                </a>
            </nav>
        </div>

        {{-- EMPTY STATE (Gambar 2.42) --}}
        <x-empty-state
            icon="calendar"
            judul="Belum ada kegiatan berlangsung"
            deskripsi="Belum ada kegiatan Posyandu yang sedang berjalan saat ini. Buat kegiatan baru untuk memulai pencatatan kehadiran warga."
            :cta="['label' => 'Buat Kegiatan Baru', 'url' => '#']"
        />

    </div>
</x-layouts.app>