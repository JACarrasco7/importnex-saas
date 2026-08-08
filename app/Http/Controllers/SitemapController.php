<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Public sitemap.xml for SEO.
     *
     * Includes:
     * - Static pages (home, marketplace, pricing, auth)
     * - Public marketplace cars (is_marketplace = true)
     *
     * Cached for 1 hour to avoid hammering the DB on each crawler request.
     */
    public function index(): Response
    {
        $cars = Cache::remember('sitemap.cars', now()->addHour(), function () {
            return Car::query()
                ->where('is_marketplace', true)
                ->orderBy('updated_at', 'desc')
                ->limit(500)
                ->get(['id', 'updated_at']);
        });

        return response()->view('sitemap', [
            'cars' => $cars,
            'lastMod' => $cars->first()?->updated_at ?? now(),
        ])->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Invalidate the sitemap cache.
     * Call this from a model observer when a public car is created/updated/deleted.
     */
    public static function flush(): void
    {
        Cache::forget('sitemap.cars');
    }
}
