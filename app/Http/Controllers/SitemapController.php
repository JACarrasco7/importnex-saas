<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Support\PublicSite;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap.cars.v2';

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
        $entries = Cache::remember(self::CACHE_KEY, now()->addHour(), function (): array {
            $sites = [];
            $urls = [];

            Car::query()
                ->publicMarketplace()
                ->with('organization')
                ->orderBy('updated_at', 'desc')
                ->limit(500)
                ->get(['id', 'organization_id', 'updated_at'])
                ->each(function (Car $car) use (&$sites, &$urls): void {
                    $organization = $car->organization;
                    if (! $organization || ! PublicSite::isAvailable($organization)) {
                        return;
                    }
                    $site = $sites[$organization->id] ??= PublicSite::forOrganization($organization);
                    $urls[] = ['loc' => $site->carUrl($car), 'lastmod' => $car->updated_at];
                });

            $homes = collect($sites)
                ->reject(fn (PublicSite $site) => $site->isPlatform)
                ->map(fn (PublicSite $site) => $site->homeUrl())
                ->values()
                ->all();

            return ['cars' => $urls, 'sites' => $homes];
        });

        return response()->view('sitemap', [
            'cars' => $entries['cars'],
            'sites' => $entries['sites'],
            'lastMod' => $entries['cars'][0]['lastmod'] ?? now(),
        ])->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Invalidate the sitemap cache.
     * Call this from a model observer when a public car is created/updated/deleted.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
