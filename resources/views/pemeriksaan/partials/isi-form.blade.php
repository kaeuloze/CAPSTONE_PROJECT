{{--
    Isi formulir pemeriksaan: tab + bagian + bagian umum + kotak hasil otomatis.
    Dipakai oleh menu Pemeriksaan (isi), Detail Riwayat (hanya-baca), dan Edit Riwayat.
    Variabel: $definisi, $nilai, $versi, $tabNonaktif.
    Opsional: $peringatan (pop-up interval per tab), $hanyaBaca (bool), $jenisDilakukan (array nilai JenisPemeriksaan,
    untuk menandai tab yang tidak diperiksa di Detail Riwayat), $namaTombol (nama tombol di catatan tab; bawaan "Tombol Submit").
--}}
@php
    $peringatan ??= [];
    $hanyaBaca ??= false;
    $jenisDilakukan ??= null;
    // Tab pertama yang tidak dikunci (mis. Ibu Nifas: tab Ibu Hamil dikunci).
    $tabAktif = collect(array_keys($definisi->tab()))->first(fn ($k) => ! isset($tabNonaktif[$k]));
    $tidakDiperiksa = fn (array $tab) => $jenisDilakukan !== null
        && ! in_array(($tab['jenis'] ?? \App\Enums\JenisPemeriksaan::Rutin)->value, $jenisDilakukan, true);
@endphp

@if (count($definisi->tab()) > 1)
    <div class="tab-form" role="tablist" aria-label="Jenis pemeriksaan">
        @foreach ($definisi->tab() as $kunciTab => $tab)
            <button type="button" role="tab" id="tab-{{ $kunciTab }}" aria-controls="panel-{{ $kunciTab }}"
                    aria-selected="{{ $kunciTab === $tabAktif ? 'true' : 'false' }}" @class(['aktif' => $kunciTab === $tabAktif])
                    data-tab="{{ $kunciTab }}"
                    @if (! $hanyaBaca && isset($tab['catatan'])) data-catatan="{{ str_replace('Tombol Submit', $namaTombol ?? 'Tombol Submit', $tab['catatan']) }}" @endif
                    @isset($peringatan[$kunciTab]) data-peringatan="{{ $peringatan[$kunciTab] }}" @endisset
                    @isset($tabNonaktif[$kunciTab]) disabled title="{{ $tabNonaktif[$kunciTab] }}" @endisset>{{ $tab['label'] }}@if ($tidakDiperiksa($tab) && ! isset($tabNonaktif[$kunciTab]))<small class="tab-ket"> · tidak diperiksa</small>@endif</button>
        @endforeach
    </div>
@endif

@foreach ($definisi->tab() as $kunciTab => $tab)
    @continue(isset($tabNonaktif[$kunciTab]))
    <div role="tabpanel" id="panel-{{ $kunciTab }}" aria-labelledby="tab-{{ $kunciTab }}" class="panel-tab" @unless ($kunciTab === $tabAktif) hidden @endunless>
        @if ($hanyaBaca && $tidakDiperiksa($tab))
            <div class="pesan info-arsip" style="margin-top:0">Pemeriksaan {{ $tab['label'] }} tidak dilakukan pada kunjungan ini.</div>
        @else
            @foreach ($tab['bagian'] as $kunciBagian)
                @include('pemeriksaan.partials.bagian', ['kunciBagian' => $kunciBagian])
            @endforeach
        @endif
    </div>
@endforeach

{{-- Bagian di bawah semua tab (mis. Penyuluhan & Rujukan). --}}
@foreach ($definisi->bagianUmum() as $kunciBagian)
    @include('pemeriksaan.partials.bagian', ['kunciBagian' => $kunciBagian])
@endforeach

@if ($definisi->viewTambahan())
    @include($definisi->viewTambahan())
@endif

@unless ($hanyaBaca)
    {{-- Bagian yang diubah kader ini (diisi otomatis oleh JavaScript; keputusan 39: simpan per bagian). --}}
    <div id="daftar-diubah">
        @foreach ((array) old('diubah', []) as $kunciDiubah)
            <input type="hidden" name="diubah[]" value="{{ $kunciDiubah }}">
        @endforeach
    </div>
@endunless
