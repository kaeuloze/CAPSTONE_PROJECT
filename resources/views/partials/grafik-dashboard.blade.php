{{--
    4 grafik batang Dashboard (dipakai Dashboard kader & halaman publik agar bentuknya SAMA, Figma 141:62).
    Variabel: $grafik (DashboardService::data()['grafik']), $filterImt, $aksiFilter (URL form Filter Kategori),
    $tersembunyi (array nama => nilai, input tersembunyi form filter).
--}}
@foreach ($grafik as $kunci => $isi)
    @php $maks = max([1, ...array_column($isi['batang'], 'nilai')]); @endphp
    <section class="kartu grafik" data-grafik="{{ $kunci }}">
        <div class="kepala-grafik">
            <div>
                <h2>{{ $isi['judul'] }}</h2>
                <p class="ket">{{ $isi['keterangan'] }}</p>
            </div>
            @if ($kunci === 'imt')
                <form method="GET" action="{{ $aksiFilter }}">
                    @foreach ($tersembunyi as $nama => $nilai)
                        <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                    @endforeach
                    <select name="kategori" aria-label="Filter Kategori" onchange="this.form.submit()">
                        @foreach (\App\Services\DashboardService::FILTER_IMT as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($filterImt === $nilai)>{{ $nilai === 'semua' ? 'Filter Kategori: Semua' : $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="tombol kecil">Terapkan</button></noscript>
                </form>
            @endif
        </div>
        <div class="batang-grafik" role="img" aria-label="{{ $isi['judul'] }}: {{ collect($isi['batang'])->map(fn ($b) => $b['label'].' '.$b['nilai'])->implode(', ') }}">
            @foreach ($isi['batang'] as $batang)
                <div class="batang" data-kunci="{{ $batang['kunci'] }}">
                    <span class="nilai-batang">{{ $batang['nilai'] }}</span>
                    <span class="isi-batang" style="height: {{ round($batang['nilai'] / $maks * 100) }}%"></span>
                    <span class="label-batang">{{ $batang['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
@endforeach
