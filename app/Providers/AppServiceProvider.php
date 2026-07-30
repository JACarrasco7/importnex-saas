<?php

namespace App\Providers;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\CarDocument;
use App\Observers\CarObserver;
use App\Observers\CarDocumentObserver;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        Car::observe(CarObserver::class);
        CarDocument::observe(CarDocumentObserver::class);

        // Hotfix: cuando la app se sirve bajo un subpath de Apache (Alias),
        // el SCRIPT_NAME se mantiene (/importnexcore/index.php) pero el
        // REQUEST_URI llega strippeado. Esto hace que url('/') genere la URL
        // sin el prefijo /importnexcore, rompiendo Ziggy y los assets.
        //
        // Forzamos el URL root con el subpath para que TODO (route(),
        // asset(), Ziggy, vue-router) genere URLs absolutas correctas.
        $appUrl = config('app.url');
        if ($appUrl && str_contains($appUrl, '/importnexcore')) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }
    }
}
