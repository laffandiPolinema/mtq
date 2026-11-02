<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Setelah SSL aktif (APP_URL https), semua URL yang dibuat aplikasi ikut memakai https.
        URL::forceHttps(str_starts_with((string) config('app.url'), 'https://'));
    }
}
