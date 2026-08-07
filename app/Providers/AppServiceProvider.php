<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Car;
use App\Models\CarDocument;
use App\Observers\AlertObserver;
use App\Observers\CarDocumentObserver;
use App\Observers\CarObserver;
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
        Alert::observe(AlertObserver::class);

        // La configuración de URL forzada se maneja en el middleware ForceBaseUrl
        // Esto permite aplicar la configuración en cada petición correctamente
    }
}
