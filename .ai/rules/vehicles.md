---
glob: 'app/Http/Controllers/CarController.php,app/Http/Controllers/CarKanbanController.php,app/Http/Controllers/PublicMarketplaceController.php,app/Http/Controllers/TripPlannerController.php,app/Http/Controllers/CarMapController.php,resources/js/Pages/Cars/**,resources/js/Pages/Public/MarketplaceIndex.vue,resources/js/Pages/Public/MarketplaceShow.vue'
title: 'Ordenamiento de listados de vehículos — precio más bajo por defecto'
---

## Regla (2026-08-15)

En TODAS las páginas que listan vehículos, el orden por defecto es **precio más bajo** (`purchase_price ASC`), salvo que el usuario pida explícitamente otro orden.

- **NUNCA** hacer 3 barridos/pasadas para "probar" órdenes. Una sola query con parámetro `sort` whitelisted.
- Opciones estándar: `price_asc` (default), `km_asc` (`mileage ASC`), `year_desc` (`SUBSTRING(year,-4) DESC`).
- No preguntar al usuario por preferencia por defecto; solo si pide un criterio fuera de la tabla estándar.
- Estado actual: `PublicMarketplaceController@index` ordena por `created_at desc` y `CarController@index` igual — migrar ambos a `price_asc` por defecto cuando se toque.
