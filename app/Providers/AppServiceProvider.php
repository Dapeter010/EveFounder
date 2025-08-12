<?php

namespace App\Providers;

use App\Services\InfoBipService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InfobipService::class, function ($app) {
            return new InfobipService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // force Https
        if (App::environment('production')) {
            URL::forceScheme('https');
        }
    }
}
