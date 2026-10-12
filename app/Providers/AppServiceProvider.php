<?php

namespace App\Providers;

use App\Events\CarImported;
use App\Listeners\NotifyImportWebhook;
use App\Models\Alert;
use App\Models\Car;
use App\Models\CarDocument;
use App\Observers\AlertObserver;
use App\Observers\CarDocumentObserver;
use App\Observers\CarObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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
        $this->registerEventListeners();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Mapeo explicito de listeners (12-sep-2026):
     * Laravel 11 auto-descubre, pero declararlo aqui hace el contrato
     * visible y permite tener varios listeners para el mismo evento.
     */
    private function registerEventListeners(): void
    {
        Event::listen(
            CarImported::class,
            NotifyImportWebhook::class,
        );
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
        // (auditoria ronda 4, sep-2026): oficinas con NAT/CGNAT comparten IP,
        // asi que un bucket por IP penaliza a todos. Si llega X-Import-Token,
        // keyar por hash del token (identificable en logs aunque sea compartido).
        RateLimiter::for('api-write', function (Request $request) {
            $token = $request->header('X-Import-Token');
            $key = $request->user()?->id
                ?: ($token ? 't:'.substr(hash('sha256', $token), 0, 16) : null)
                ?: $request->ip();

            return Limit::perMinute(20)->by($key);
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

        // 60 req/min — Stripe webhook (auditoria ronda 4, sep-2026).
        // Stripe valida HMAC de cada payload, asi que un atacante puede
        // forzar verificacion criptografica con spam de eventos invalidos.
        // 60/min cubre de sobra los reintentos reales de Stripe.
        RateLimiter::for('stripe-webhook', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    private function registerObservers(): void
    {
        Car::observe(CarObserver::class);
        CarDocument::observe(CarDocumentObserver::class);
        Alert::observe(AlertObserver::class);
    }
}
