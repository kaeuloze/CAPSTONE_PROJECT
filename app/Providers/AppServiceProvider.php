<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batas total percobaan login per menit dari satu alamat IP (semua username).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) config('posyandu.login.maks_per_ip_per_menit'))
                ->by($request->ip())
                ->response(fn () => back()
                    ->withErrors(['username' => 'Terlalu banyak percobaan masuk dari perangkat ini. Silakan tunggu sebentar.'])
                    ->onlyInput('username'));
        });
    }
}
