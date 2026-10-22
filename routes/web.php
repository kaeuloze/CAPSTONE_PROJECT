<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\PemeriksaanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\RekapitulasiController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\VaksinController;
use App\Http\Controllers\VaksinasiController;
use App\Http\Controllers\WargaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HALAMAN PUBLIK  (tanpa login)
|--------------------------------------------------------------------------
*/

Route::get('/', [BerandaController::class, 'index'])
    ->middleware('throttle:publik')
    ->name('beranda');

/*
|--------------------------------------------------------------------------
| TAMU  (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get(config('posyandu.login.path'), [LoginController::class, 'create'])->name('login');
    Route::post(config('posyandu.login.path'), [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

/*
|--------------------------------------------------------------------------
| KADER  (sudah login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard (SKF-03) - ?tab=bulan_ini|arsip, ?bulan=&tahun=, ?kategori=
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/excel', [DashboardController::class, 'ekspor'])->name('dashboard.ekspor');
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
        Route::get('presensi/status', [PresensiController::class, 'status'])->name('presensi.status');
        Route::post('presensi/{warga}', [PresensiController::class, 'atur'])
            ->where('warga', '[0-9]{16}')
            ->name('presensi.atur');
    });

    // Pemeriksaan (SKF-07 & SKF-08). Parameter {warga} = NIK.
    Route::prefix('pemeriksaan')->name('pemeriksaan.')->group(function () {
        Route::get('/', [PemeriksaanController::class, 'index'])->name('index');
        Route::get('status', [PemeriksaanController::class, 'status'])->name('status');
        Route::get('{warga}', [PemeriksaanController::class, 'form'])
            ->where('warga', '[0-9]{16}')->name('form');
        Route::post('{warga}', [PemeriksaanController::class, 'simpan'])
            ->where('warga', '[0-9]{16}')->name('simpan');
    });

    // Riwayat Pemeriksaan (SKF-09). Parameter {warga} = NIK.
    Route::prefix('riwayat')->name('riwayat.')->group(function () {
        Route::get('/', [RiwayatController::class, 'index'])->name('index');
        Route::get('warga/{warga}', [RiwayatController::class, 'warga'])
            ->where('warga', '[0-9]{16}')->name('warga');

        Route::prefix('pemeriksaan/{pemeriksaan}')->whereNumber('pemeriksaan')->group(function () {
            Route::get('/', [RiwayatController::class, 'show'])->name('show');
            Route::get('edit', [RiwayatController::class, 'edit'])->name('edit');
            Route::put('/', [RiwayatController::class, 'update'])->name('update');
            Route::delete('/', [RiwayatController::class, 'destroy'])->name('destroy');
        });
    });

    // Vaksinasi Balita (SKF-10). Parameter {warga} = NIK balita.
    Route::prefix('vaksinasi')->name('vaksinasi.')->group(function () {
        Route::get('/', [VaksinasiController::class, 'index'])->name('index');
        Route::get('status', [VaksinasiController::class, 'status'])->name('status');
        Route::get('{warga}', [VaksinasiController::class, 'form'])
            ->where('warga', '[0-9]{16}')->name('form');
        Route::post('{warga}', [VaksinasiController::class, 'simpan'])
            ->where('warga', '[0-9]{16}')->name('simpan');
    });

    // Master Vaksin (pendukung SKF-10).
    Route::resource('vaksin', VaksinController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->where(['vaksin' => '[0-9]+']);

    // Rekapitulasi + Ekspor Excel (SKF-12).
    Route::prefix('rekapitulasi')->name('rekapitulasi.')->group(function () {
        Route::get('/', [RekapitulasiController::class, 'index'])->name('index');
        Route::get('{kategori}', [RekapitulasiController::class, 'show'])->name('show');
        Route::get('{kategori}/excel', [RekapitulasiController::class, 'ekspor'])->name('ekspor');
    });

    // Manajemen Pengguna / akun kader (SKF-11).
    Route::resource('pengguna', PenggunaController::class)
        ->except(['show'])
        ->parameters(['pengguna' => 'pengguna'])
        ->where(['pengguna' => '[0-9]+']);
});