<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\PemeriksaanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\RiwayatController;
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

    // Kegiatan & Presensi (SKF-06). Parameter {kegiatan} = id_kegiatan.
    Route::resource('kegiatan', KegiatanController::class)
        ->except(['show'])
        ->where(['kegiatan' => '[0-9]+']);
    Route::prefix('kegiatan/{kegiatan}')->whereNumber('kegiatan')->group(function () {
        Route::post('selesaikan', [KegiatanController::class, 'selesaikan'])->name('kegiatan.selesaikan');
        Route::get('presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::get('presensi/status', [PresensiController::class, 'status'])->name('presensi.status'); // polling realtime
        Route::post('presensi/{warga}', [PresensiController::class, 'atur'])
            ->where('warga', '[0-9]{16}')
            ->name('presensi.atur');
    });

    // Pemeriksaan (SKF-07 & SKF-08). Parameter {warga} = NIK; kegiatan = kegiatan yang sedang berlangsung.
    Route::prefix('pemeriksaan')->name('pemeriksaan.')->group(function () {
        Route::get('/', [PemeriksaanController::class, 'index'])->name('index');
        Route::get('status', [PemeriksaanController::class, 'status'])->name('status'); // polling realtime
        Route::get('{warga}', [PemeriksaanController::class, 'form'])->where('warga', '[0-9]{16}')->name('form');
        Route::post('{warga}', [PemeriksaanController::class, 'simpan'])->where('warga', '[0-9]{16}')->name('simpan');
    });

    // Riwayat Pemeriksaan. {warga} = NIK; {pemeriksaan} = id_pemeriksaan (hanya yang sudah Submit).
    Route::prefix('riwayat')->name('riwayat.')->group(function () {
        Route::get('/', [RiwayatController::class, 'index'])->name('index');
        Route::get('warga/{warga}', [RiwayatController::class, 'warga'])->where('warga', '[0-9]{16}')->name('warga');
        Route::prefix('pemeriksaan/{pemeriksaan}')->whereNumber('pemeriksaan')->group(function () {
            Route::get('/', [RiwayatController::class, 'show'])->name('show');
            Route::get('edit', [RiwayatController::class, 'edit'])->name('edit');
            Route::put('/', [RiwayatController::class, 'update'])->name('update');
            Route::delete('/', [RiwayatController::class, 'destroy'])->name('destroy');
        });
    });
});
