<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WargaController;
use Illuminate\Support\Facades\Route;

// Halaman publik (Profil Posyandu) — sementara memakai halaman bawaan, dibuat di tahap frontend.
Route::view('/', 'welcome')->name('beranda');

// Hanya untuk tamu (belum login).
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login') // batas percobaan per alamat IP
        ->name('login.store');
});

// Hanya untuk kader yang sudah login.
Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard'); // sementara; isi Dashboard dibuat di Tahap 4
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Data Warga (SKF-05). Parameter {warga} = NIK.
    Route::resource('warga', WargaController::class)
        ->parameters(['warga' => 'warga'])
        ->where(['warga' => '[0-9]{16}']);
});
