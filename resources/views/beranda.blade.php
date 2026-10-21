{{--
    Halaman publik / Profil Posyandu SEMENTARA (Figma 141:62 Landing Page, SKF-01). Tampilan akhir oleh tim frontend.
    Data: $profil (config posyandu.profil), $periode, $filterImt, $statistik (grafik, ada_kegiatan), $daftarTahun, $kegiatanTerakhir.
    Tidak ada tautan ke halaman login (keputusan pengguna: kader membuka alamat login secara langsung).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $profil['nama'] }} — {{ $profil['wilayah'] }}">
    <title>{{ $profil['nama'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/sementara.css') }}">
</head>
<body class="publik">
<header class="kepala-publik">
    <a href="{{ route('beranda') }}" class="merek-publik"><span class="logo" aria-hidden="true"></span>{{ $profil['nama'] }}</a>
    <nav aria-label="Menu halaman">
        <a href="#profil">Profil</a>
        <a href="#jadwal">Jadwal</a>
        <a href="#kontak">Kontak</a>
    </nav>
</header>

<section class="pahlawan">
    <h1>Selamat Datang di {{ $profil['nama'] }}</h1>
    <p>Pelayanan kesehatan masyarakat — {{ $profil['wilayah'] }}</p>
</section>

<main class="badan-publik">
    <section class="kartu" id="profil">
        <h2>Profil Posyandu</h2>
        <p class="ket">Informasi umum &amp; profil pelayanan</p>
        <p>{{ $profil['deskripsi'] }}</p>
    </section>

    <section class="kartu" id="jadwal">
        <h2>Jadwal Kegiatan</h2>
        <p class="ket">Hari &amp; jam pelaksanaan posyandu</p>
        <p>{{ $profil['jadwal'] }}@if (filled($profil['jam'])) Pukul {{ $profil['jam'] }}.@endif</p>
        @if ($kegiatanTerakhir)
            <p class="ket">Kegiatan posyandu terakhir: {{ $kegiatanTerakhir->translatedFormat('l, j F Y') }}</p>
        @endif
    </section>

    <section class="kartu" id="kontak">
        <h2>Kontak</h2>
        <p class="ket">Alamat &amp; narahubung</p>
        <p>{{ $profil['alamat'] }}</p>
        @if (filled($profil['narahubung']) || filled($profil['telepon']))
            <p>Narahubung: {{ collect([$profil['narahubung'], $profil['telepon']])->filter()->implode(' · ') }}</p>
        @endif
    </section>

    <div class="info">Statistik kesehatan agregat (tanpa identitas). Grafik di bawah memakai bentuk yang sama dengan Dashboard kader.</div>

    <form method="GET" action="{{ route('beranda') }}" class="saring" aria-label="Pilih periode statistik" style="margin-top:16px">
        @if ($filterImt !== 'semua')<input type="hidden" name="kategori" value="{{ $filterImt }}">@endif
        <select name="bulan" aria-label="Bulan">
            @foreach (range(1, 12) as $b)
                <option value="{{ $b }}" @selected($b === $periode->bulan)>{{ \Carbon\CarbonImmutable::create(2000, $b, 1)->translatedFormat('F') }}</option>
            @endforeach
        </select>
        <select name="tahun" aria-label="Tahun">
            @foreach ($daftarTahun as $t)
                <option value="{{ $t }}" @selected($t === $periode->tahun)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit" class="tombol">Tampilkan</button>
        <span>Menampilkan data: <strong>{{ $periode->label() }}</strong></span>
    </form>

    @unless ($statistik['ada_kegiatan'])
        <div class="pesan info-arsip">Belum ada kegiatan posyandu pada {{ $periode->label() }}.</div>
    @endunless

    <div class="kisi-grafik">
        @include('partials.grafik-dashboard', [
            'grafik' => $statistik['grafik'],
            'filterImt' => $filterImt,
            'aksiFilter' => route('beranda'),
            'tersembunyi' => ['bulan' => $periode->bulan, 'tahun' => $periode->tahun],
        ])
    </div>
</main>

<footer class="kaki-publik">&copy; {{ now()->year }} {{ $profil['nama'] }} · {{ $profil['wilayah'] }}</footer>
</body>
</html>
