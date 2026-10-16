<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login & Logout kader (Activity Diagram 2.4).
 */
class LoginController extends Controller
{
    /** Menampilkan halaman login. */
    public function create(): View
    {
        return view('auth.login');
    }

    /** Memproses login, membuat sesi baru, lalu menuju Dashboard. */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Sesi diganti baru untuk mencegah pembajakan sesi (session fixation).
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Logout: menghapus sesi secara aman lalu kembali ke halaman login. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
