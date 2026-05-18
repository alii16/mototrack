<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Daftarkan TelegramService sebagai singleton
        $this->app->singleton(TelegramService::class, function () {
            return new TelegramService();
        });
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // URL::forceScheme('https');

        // URL::forceRootUrl(env('APP_URL'));
    }
}
