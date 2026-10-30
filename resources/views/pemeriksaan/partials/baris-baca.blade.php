{{--
    Satu baris hanya-baca di kartu Detail Riwayat Pemeriksaan (label di atas, nilai di bawah).
    Dipakai oleh partial isian / centang / tekanan-darah saat $modeBaca = true, sehingga label dan
    pilihan jawaban yang tampil sama persis dengan formulir pemeriksaan.
    Variabel: $label, $teks (null = kosong), $satuan (opsional).
--}}
<div class="baris-baca min-w-0">
    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
    <dd @class(['mt-1 text-[14px] text-slate-800', 'italic text-slate-400' => $teks === null])>
        {{ $teks ?? '—' }}
        @if ($teks !== null && ($satuan ?? null))
            <span class="ml-1 text-[11px] font-medium text-slate-400">{{ $satuan }}</span>
        @endif
    </dd>
</div>
