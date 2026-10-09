{{-- Dashboard SEMENTARA untuk menguji login/logout. Isi Dashboard (grafik IMT & PTM) dibuat di Tahap 4. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f3f4f6; color: #1f2937; padding: 24px 16px; }
        .isi { max-width: 720px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { color: #4b5563; }
        .tombol { padding: 10px 18px; border-radius: 8px; border: 1px solid #1f2937; background: #fff; cursor: pointer; font-size: 14px; }
        .tombol.utama { background: #1f2937; color: #fff; }
        dialog { border: 0; border-radius: 16px; padding: 24px; width: min(92vw, 420px); }
        dialog::backdrop { background: rgba(0, 0, 0, .4); }
        .baris { display: flex; gap: 12px; margin-top: 20px; }
        .baris > * { flex: 1; }
    </style>
</head>
<body>
    <main class="isi">
        <h1>Dashboard</h1>
        <p>Selamat datang, <strong>{{ auth()->user()->nama_kader }}</strong>. Halaman ini sementara untuk menguji login.</p>
        <button type="button" class="tombol" onclick="document.getElementById('dialog-logout').showModal()">Logout</button>
    </main>

    {{-- Dialog konfirmasi logout (wireframe "Dialog Konfirmasi Logout"). --}}
    <dialog id="dialog-logout">
        <strong>Apakah Anda yakin ingin keluar dari akun ini?</strong>
        <div class="baris">
            <button type="button" class="tombol" onclick="document.getElementById('dialog-logout').close()">Tidak</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="tombol utama" style="width:100%">Ya</button>
            </form>
        </div>
    </dialog>
</body>
</html>
