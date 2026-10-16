{{-- Navigasi halaman sederhana. Variabel: $data (LengthAwarePaginator), $satuan (mis. "warga"). --}}
@if ($data->total() > 0)
    <nav class="halaman" aria-label="Navigasi halaman">
        <span>Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ $data->total() }} {{ $satuan }}</span>
        @if ($data->hasPages())
            <span style="display:flex;gap:8px;align-items:center">
                <a class="tombol kecil" href="{{ $data->previousPageUrl() ?? '#' }}" @if ($data->onFirstPage()) aria-disabled="true" @endif>‹ Sebelumnya</a>
                <span>Halaman {{ $data->currentPage() }} dari {{ $data->lastPage() }}</span>
                <a class="tombol kecil" href="{{ $data->nextPageUrl() ?? '#' }}" @unless ($data->hasMorePages()) aria-disabled="true" @endunless>Berikutnya ›</a>
            </span>
        @endif
    </nav>
@endif
