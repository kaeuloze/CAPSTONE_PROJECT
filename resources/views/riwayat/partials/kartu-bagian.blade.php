{{--
    Satu kartu bagian di Detail Riwayat Pemeriksaan (desain kartu Maura).
    Isinya memakai tampilan formulir pemeriksaan yang sama dalam mode baca, jadi label & pilihan jawaban
    selalu sama dengan formulir. Variabel: $definisi, $bagian, $kunci, $isi (+ $warga, $kegiatan dari halaman).
--}}
<div class="mt-3 card overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-3.5">
        <h3 class="text-sm font-bold uppercase tracking-wide text-mawar-800">{{ $bagian->judul }}</h3>
    </div>

    <dl class="rincian-baca grid gap-x-6 gap-y-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
        @include($definisi->view() . '.' . $kunci, ['modeBaca' => true])
    </dl>
</div>
