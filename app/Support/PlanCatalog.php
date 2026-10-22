<?php

namespace App\Support;

/**
 * Planes tal y como se enseñan (portada, precios, facturación). Sale de
 * config/subscription.php, que es también lo que cobra Stripe y lo que limita
 * la app: así ninguna pantalla promete algo que el plan no da.
 */
final class PlanCatalog
{
    /** Límites que se enseñan, en este orden. */
    public const LIMITS = ['cars', 'clients', 'contacts', 'users'];

    /**
     * @return array<int, array{key: string, name: string, price: int|float, description: string, limits: array<string, int>, features: array<string, bool>}>
     */
    public static function all(): array
    {
        $plans = [];
        foreach ((array) config('subscription.plans', []) as $key => $plan) {
            $plans[] = [
                'key' => (string) $key,
                'name' => (string) ($plan['name'] ?? ucfirst((string) $key)),
                'price' => $plan['price'] ?? 0,
                'description' => (string) ($plan['description'] ?? ''),
                'limits' => collect(self::LIMITS)
                    ->mapWithKeys(fn (string $limit) => [$limit => (int) ($plan[$limit.'_limit'] ?? 0)])
                    ->all(),
                'features' => array_map(fn ($value) => (bool) $value, (array) ($plan['features'] ?? [])),
            ];
        }

        return $plans;
    }
}
