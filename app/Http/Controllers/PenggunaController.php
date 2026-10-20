<?php

namespace App\Http\Controllers;

use App\Exceptions\AksiDitolakException;
use App\Exceptions\DataSudahBerubahException;
use App\Http\Requests\Pengguna\SimpanPenggunaRequest;
use App\Models\Kader;
use App\Services\PenggunaService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menu Manajemen Pengguna (SKF-11): Kelola Data Akun (Figma 141:164, Gambar 2.67) dan
 * Tambah / Edit Akun Kader (Figma 141:2478, Gambar 2.68). Parameter {pengguna} = id_kader.
 */
class PenggunaController extends Controller
{
    public function __construct(private readonly PenggunaService $layanan)
    {
    }

    public function index(Request $request): View
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('pengguna.index', [
            'daftarAkun' => $this->layanan->daftar($filter['cari'] ?? null),
            'cari' => $filter['cari'] ?? '',
        ]);
    }

    public function create(): View
    {
        return view('pengguna.form', ['akun' => new Kader]);
    }

    public function store(SimpanPenggunaRequest $request): RedirectResponse
    {
        try {
            $akun = $this->layanan->tambah($request->dataAkun(), $request->sandiBaru());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['username' => 'Username ini sudah dipakai akun lain.']);
        }

        return redirect()->route('pengguna.index')->with('sukses', "Akun {$akun->nama_kader} berhasil dibuat.");
    }

    public function edit(Kader $pengguna): View
    {
        return view('pengguna.form', ['akun' => $pengguna]);
    }

    public function update(SimpanPenggunaRequest $request, Kader $pengguna): RedirectResponse
    {
        $akunSendiri = Auth::id() === $pengguna->getKey();
        $sandiBaru = $request->sandiBaru();

        try {
            $akun = $this->layanan->ubah($pengguna, $request->dataAkun(), $sandiBaru, $request->validated('versi'),
                $akunSendiri ? $request->session()->getId() : null);
        } catch (DataSudahBerubahException $e) {
            return back()->withInput($request->except('password', 'password_confirmation'))->with('galat', $e->getMessage());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['username' => 'Username ini sudah dipakai akun lain.']);
        }

        if ($akunSendiri && $sandiBaru !== null) {
            // Kata sandi sendiri diganti: sesi diganti baru (sesi lama dihapus), perangkat lain sudah dikeluarkan.
            $request->session()->regenerate(true);
        }

        $pesan = "Perubahan akun {$akun->nama_kader} berhasil disimpan.";
        if ($sandiBaru !== null) {
            $pesan .= $akunSendiri
                ? ' Perangkat lain yang memakai akun ini sudah dikeluarkan.'
                : ' Akun ini perlu login ulang dengan kata sandi baru.';
        }

        return redirect()->route('pengguna.index')->with('sukses', $pesan);
    }

    public function destroy(Request $request, Kader $pengguna): RedirectResponse
    {
        try {
            $this->layanan->hapus($pengguna);
        } catch (AksiDitolakException $e) {
            return back()->with('galat', $e->getMessage());
        }

        if (Auth::id() === $pengguna->getKey()) {
            // Kader menghapus akunnya sendiri: sesinya diakhiri lalu kembali ke halaman login.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('sukses', 'Akun Anda sudah dihapus. Silakan masuk dengan akun lain.');
        }

        return redirect()->route('pengguna.index')->with('sukses', "Akun {$pengguna->nama_kader} berhasil dihapus.");
    }
}
