<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Car;
use App\Models\CarDocument;
use App\Observers\AlertObserver;
use App\Observers\CarDocumentObserver;
use App\Observers\CarObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiters();
        $this->registerObservers();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Centralized rate limit definitions.
     *
     * Use in routes:
     *   Route::post(...)->middleware('throttle:api-write');
     *
     * Available tiers:
     *   - api-read       60 req/min  (safe read APIs)
     *   - api-write      20 req/min  (mutations)
     *   - api-heavy       5 req/10min (scraping, AI calls)
     *   - auth            6 req/min  (login/register/forgot)
     *   - public-form    10 req/min  (newsletter, contact)
     */
    private function configureRateLimiters(): void
    {
        // 60 req/min — read-only APIs (marketplace, public data)
        RateLimiter::for('api-read', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // 20 req/min — write/mutation APIs (POST/PUT/PATCH/DELETE)
        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // 5 req/10min — heavy operations (AI verification, scraping)
        RateLimiter::for('api-heavy', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->user()?->id ?: $request->ip());
        });

        // 6 req/min — auth attempts (login, register, password reset)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        // 10 req/min — public forms (newsletter, car request, contact)
        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    private function registerObservers(): void
    {
        Car::observe(CarObserver::class);
        CarDocument::observe(CarDocumentObserver::class);
        Alert::observe(AlertObserver::class);
    }
}
