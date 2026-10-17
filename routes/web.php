<?php

use Illuminate\Support\Facades\Route;
/*
Route::get('/', function () {
    return view('welcome');
});
*/

// ===== HALAMAN PUBLIK =====
Route::view('/', 'landing.index')->name('landing');
Route::view('/login', 'auth.login')->name('login');

// ===== HALAMAN ADMIN (dummy) =====
Route::view('/kegiatan', 'kegiatan.index')->name('kegiatan');
Route::view('/pemeriksaan', 'pemeriksaan.index')->name('pemeriksaan');
Route::view('/warga', 'warga.index')->name('warga');