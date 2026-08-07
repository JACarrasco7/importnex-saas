---
glob: 'app/Http/Controllers/PublicMarketplaceController.php,resources/js/Pages/Public/**,resources/js/Components/WishlistButton.vue,resources/js/Components/CompareBar.vue,resources/js/Components/FinancingCalculator.vue,resources/js/Composables/useWishlist.js'
title: 'Marketplace público — engagement y viralidad'
---

## Arquitectura (2026-08-07)

Marketplace público accesible sin auth en `/marketplace` y `/marketplace/{car}` con:

- **Filtros server-side**: 12 filtros whitelisted en `PublicMarketplaceController@index` (`FILTER_RULES` constant).
- **Paginación**: 12 coches/página. Backend filtra, NO cliente.
- **Visibilidad**: `is_public=true` (organization) + `is_marketplace=true` (car) + `status=Delivered` + `verdict IN (Buy, Buy if price drops)`.

## Engagement features (2026-08-07)

1. **Wishlist** (`useWishlist` composable + `WishlistButton`):
   - localStorage persistente (clave `importnex_wishlist`).
   - SSR-safe (`typeof window` guard).
   - Toggle heart icon. HeartIconSolid cuando activo.

2. **Comparador** (`CompareBar` component + `/marketplace/compare`):
   - Bottom bar flotante cuando wishlist ≥1 item.
   - Hasta 4 coches. Click "Comparar" navega a `/marketplace/compare?ids=...`.
   - Vista comparativa con tabla side-by-side (11 features).

3. **Calculadora financiación** (`FinancingCalculator`):
   - Sliders reactivos: precio, entrada %, plazo meses, tasa %.
   - Fórmula interés compuesto. Display destacado con gradiente estoril.

4. **Newsletter popup** (`NewsletterPopup`):
   - 30s delay + 30 días localStorage cooldown.
   - POST real a `/newsletter/subscribe` con rate limit.

5. **URL compartible**:
   - `syncToUrl()` mantiene 11 query params sincronizados con `window.history.replaceState`.
   - Botón "Limpiar" aparece solo si hay filtros activos.

## Backend routes (NO MEMORIZAR — buscar en routes/web.php)

- `GET /marketplace` → `PublicMarketplaceController@index`
- `GET /marketplace/compare` → `PublicMarketplaceController@compare` (max 4 IDs via `?ids=1,2,3,4`)
- `GET /marketplace/{car}` → `PublicMarketplaceController@show` (con `marketplace_views` counter)
- `POST /newsletter/subscribe` → `NewsletterController@subscribe` (rate limit 5/min/IP)
- `DELETE /newsletter/unsubscribe` → `NewsletterController@unsubscribe`

## Patrón de filtros server-side

```php
$filters = $validator->valid(); // whitelist
$cars = Car::query()
    ->whereHas('organization', fn($q) => $q->where('is_public', true))
    ->where('is_marketplace', true)
    ->whereIn('status', ['Delivered'])
    ->whereIn('verdict', ['Buy', 'Buy if price drops'])
    ->when($filters['search'] ?? null, fn($q, $s) => /* search */)
    ->when($filters['brand'] ?? null, fn($q, $b) => $q->where('brand', $b))
    // ... todos los filtros
    ->paginate(12)
    ->withQueryString();
```

## Tests obligatorios

- `tests/Feature/MarketplaceEnhancementsTest.php` (8 tests: view counter, newsletter, rate limit)
- `php artisan test --filter="Marketplace"`