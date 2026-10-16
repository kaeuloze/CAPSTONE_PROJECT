{{-- Kerangka halaman kader SEMENTARA (meniru sidebar wireframe). Akan diganti HTML tim frontend tanpa mengubah logika. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/sementara.css') }}">
</head>
<body>
@php
    // Menu sesuai urutan sidebar wireframe. 'rute' = null berarti modul belum dibuat.
    $menu = [
        ['Dashboard', 'dashboard', ['dashboard']],
        ['Kegiatan', 'kegiatan.index', ['kegiatan.*', 'presensi.*']],
        ['Data Warga', 'warga.index', ['warga.*']],
        ['Pemeriksaan', 'pemeriksaan.index', ['pemeriksaan.*']],
        ['Vaksinasi Balita', 'vaksinasi.index', ['vaksinasi.*']],
        ['Master Vaksin', 'vaksin.index', ['vaksin.*']],
        ['Riwayat Pemeriksaan', 'riwayat.index', ['riwayat.*']],
        ['Manajemen Pengguna', null, null],
        ['Rekapitulasi', null, null],
    ];
@endphp
<div class="tata-letak">
    <aside class="sidebar">
        <div class="merek">
            <span style="display:flex;align-items:center;gap:10px"><span class="logo" aria-hidden="true"></span>Posyandu Mawar 1</span>
            <button type="button" class="tombol kecil tombol-menu" aria-expanded="false" aria-controls="menu-utama">Menu</button>
        </div>
        <nav aria-label="Menu utama">
            <ul class="menu" id="menu-utama">
                @foreach ($menu as [$nama, $rute, $pola])
                    <li>
                        @if ($rute)
                            <a href="{{ route($rute) }}" @class(['aktif' => request()->routeIs(...$pola)])
                               @if (request()->routeIs(...$pola)) aria-current="page" @endif><i aria-hidden="true"></i>{{ $nama }}</a>
                        @else
                            <span class="nonaktif" title="Modul ini belum tersedia"><i aria-hidden="true"></i>{{ $nama }}</span>
                        @endif
                    </li>
                @endforeach
                <li><button type="button" data-buka-dialog="dialog-logout"><i aria-hidden="true"></i>Logout</button></li>
            </ul>
        </nav>
    </aside>

    <div class="konten">
        <header class="kepala">
            <div>
                <h1>@yield('judul')</h1>
                @hasSection('subjudul')<p>@yield('subjudul')</p>@endif
            </div>
            @yield('aksi-kepala')
        </header>

        <main class="badan">
            @if (session('sukses'))
                <div class="pesan sukses" role="status">{{ session('sukses') }}</div>
            @endif
            @if (session('galat'))
                <div class="pesan galat" role="alert">{{ session('galat') }}</div>
            @endif

            @yield('isi')
        </main>
    </div>
</div>

{{-- Dialog konfirmasi logout (wireframe). --}}
<dialog id="dialog-logout" aria-labelledby="judul-logout">
    <h3 id="judul-logout">Apakah Anda yakin ingin keluar dari akun ini?</h3>
    <div class="dialog-tombol">
        <button type="button" class="tombol" data-tutup-dialog>Tidak</button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="tombol utama">Ya</button>
        </form>
    </div>
</dialog>

<script src="{{ asset('js/sementara.js') }}"></script>
@stack('skrip')
</body>
</html>
