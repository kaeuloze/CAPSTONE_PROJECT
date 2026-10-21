<?php

namespace App\Providers;

use App\Services\Pemeriksaan\Form\FormDewasa;
use App\Services\Pemeriksaan\Form\FormIbuHamil;
use App\Services\Pemeriksaan\Form\FormIbuNifas;
use App\Services\Pemeriksaan\Form\FormLansia;
use App\Services\Pemeriksaan\Form\FormRemaja;
use App\Services\Pemeriksaan\KatalogForm;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\NotPwnedVerifier;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Daftar formulir pemeriksaan yang sudah tersedia (ditambah satu per satu, keputusan 39).
        $this->app->singleton(KatalogForm::class, function () {
            $katalog = new KatalogForm;
            $katalog->daftarkan(new FormRemaja);
            $katalog->daftarkan(new FormDewasa);
            $katalog->daftarkan(new FormLansia);
            $katalog->daftarkan(new FormIbuHamil);
            $katalog->daftarkan(new FormIbuNifas);

            return $katalog;
        });

        // Pengecekan sandi bocor (Manajemen Pengguna) memakai batas waktu singkat agar halaman tidak menunggu lama
        // bila hosting tidak bisa menghubungi layanan Have I Been Pwned (bawaan Laravel 30 detik).
        $this->app->singleton(UncompromisedVerifier::class, fn ($app) => new NotPwnedVerifier(
            $app->make(HttpFactory::class),
            (int) config('posyandu.pengguna.cek_sandi_bocor_timeout_detik'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Halaman publik: batas permintaan per menit per alamat IP (melindungi server dari banjir pengunjung/bot).
        RateLimiter::for('publik', fn (Request $request) => Limit::perMinute((int) config('posyandu.publik.maks_per_menit'))->by($request->ip()));

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
