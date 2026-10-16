{{-- Halaman login SEMENTARA (meniru wireframe "Login"). Tampilan akhir mengikuti HTML tim frontend. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #f3f4f6; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #1f2937; padding: 16px; }
        .kartu { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px; }
        .logo { width: 56px; height: 56px; border-radius: 50%; background: #e5e7eb; margin: 0 auto 16px; }
        h1 { font-size: 20px; font-weight: 600; text-align: center; margin: 0; }
        .sub { text-align: center; font-size: 13px; color: #6b7280; margin: 4px 0 24px; }
        label { display: block; font-size: 13px; color: #4b5563; margin-bottom: 6px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; margin-bottom: 16px; }
        input:focus { outline: 2px solid #9ca3af; border-color: #9ca3af; }
        button { width: 100%; padding: 11px; border: 0; border-radius: 8px; background: #1f2937; color: #fff; font-size: 15px; cursor: pointer; }
        button:disabled { opacity: .6; cursor: wait; }
        .galat { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
        .catatan { text-align: center; font-size: 12px; color: #9ca3af; margin: 20px 0 0; }
    </style>
</head>
<body>
    <main class="kartu">
        <div class="logo" aria-hidden="true"></div>
        <h1>Masuk Sistem Posyandu</h1>
        <p class="sub">Khusus kader / petugas</p>

        @if ($errors->any())
            <div class="galat" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" id="form-login">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}"
                   maxlength="50" autocomplete="username" required autofocus>

            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password"
                   maxlength="64" autocomplete="current-password" required>

            <button type="submit" id="tombol-masuk">Masuk</button>
        </form>

        <p class="catatan">Mendukung lebih dari satu petugas (kader login dengan akun masing-masing di perangkat berbeda).</p>
    </main>

    <script>
        // Status memuat: cegah klik ganda dan beri tanda sedang diproses (SOP UI).
        document.getElementById('form-login').addEventListener('submit', function () {
            const tombol = document.getElementById('tombol-masuk');
            tombol.disabled = true;
            tombol.textContent = 'Memproses...';
        });
    </script>
</body>
</html>
