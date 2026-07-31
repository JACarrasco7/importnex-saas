<?php

namespace App\Providers;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\CarDocument;
use App\Observers\CarObserver;
use App\Observers\CarDocumentObserver;
use Illuminate\Support\Facades\URL;
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

        // Forzar URL base con prefijo para que TODOS los redirects (incluido Authenticate)
        // generen Location con /importnexcore/...
        // CRÍTICO: aplicar aquí en boot() para que esté disponible ANTES de cualquier middleware
        $appUrl = config('app.url');
        if ($appUrl) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
