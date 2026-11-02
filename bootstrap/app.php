<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman kader diarahkan ke login;
        // kader yang sudah login dan membuka halaman login diarahkan ke Dashboard.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // SOP poin 4: batas ukuran request (±10 KB). Di grup web, dijalankan tepat setelah sesi dimulai
        // (agar pesan galat bisa ditampilkan di halaman sebelumnya) dan sebelum data lain diproses.
        $middleware->web(append: [\App\Http\Middleware\BatasiUkuranRequest::class]);
        $middleware->appendToPriorityList(
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\BatasiUkuranRequest::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
