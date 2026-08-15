---
name: vehicle-listings
description: "Regla de ordenamiento de listados de vehículos en ImportnexCore. Actívala siempre que implementes, modifiques o consultes páginas que listan vehículos/coches: marketplace público (Public/MarketplaceIndex), Cars/Index, kanban, trips, dashboard, mapas o cualquier listado que muestre cars con precio. La convención: por defecto SIEMPRE ordenar por precio más bajo (purchase_price ASC), salvo que el usuario pida explícitamente otro orden. Opciones estándar: price_asc (defecto), km_asc, year_desc. NUNCA hacer múltiples barridos/pasadas para ordenar."
license: MIT
metadata:
  author: importnexcore
---

# Vehicle Listings — Ordenamiento por defecto

## Regla de oro

En TODAS las páginas que listan vehículos, el orden por defecto es **precio más bajo primero** (`purchase_price ASC`).

Solo se ordena por otra cosa si el usuario lo pide explícitamente (p. ej. "ordena por km", "ordena por más nuevo").

## Opciones de ordenamiento estándar

| `sort` value | Significado | Query |
| --- | --- | --- |
| `price_asc` | Precio más bajo (DEFAULT) | `->orderBy('purchase_price', 'asc')` |
| `km_asc` | KM más baja | `->orderBy('mileage', 'asc')` |
| `year_desc` | Más nuevo | `->orderByRaw('SUBSTRING(year, -4) DESC')` |

## Cómo implementar (NO hagas 3 barridos)

- Se implementa **UN único parámetro** `sort` (whitelist de los 3 valores de la tabla) con default `price_asc`.
- Una sola query con `->when($sort, ...)` que aplica el `orderBy` según el valor. Nunca lanzar 3 consultas/pasadas para "ordenar de cada forma" — es ineficiente e innecesario.
- Validar con whitelist como el resto de filtros (ver `PublicMarketplaceController::FILTER_RULES`).
- El frontend manda `sort` como query param; el backend nunca asume orden del cliente sin pasar por la whitelist.
- Ejemplo patrón:

```php
private const SORT_RULES = [
    'sort' => ['nullable', 'string', 'in:price_asc,km_asc,year_desc'],
];

$sort = $filters['sort'] ?? 'price_asc';
$cars = Car::query()
    ->when($sort === 'km_asc', fn ($q) => $q->orderBy('mileage', 'asc'))
    ->when($sort === 'year_desc', fn ($q) => $q->orderByRaw('SUBSTRING(year, -4) DESC'))
    ->when($sort === 'price_asc' || ! in_array($sort, ['km_asc', 'year_desc']), fn ($q) => $q->orderBy('purchase_price', 'asc'))
    ->paginate(12)
    ->withQueryString();
```

## Cuándo preguntar al usuario

- **NO** preguntar por defecto. Usar `price_asc` siempre.
- Solo preguntar si el usuario pide un criterio que no está en la tabla estándar (p. ej. "por marca", "por ahorro estimado").

## Páginas afectadas (verificar al tocar)

- `resources/js/Pages/Public/MarketplaceIndex.vue` + `PublicMarketplaceController@index`
- `resources/js/Pages/Cars/Index.vue` + `CarController@index` (hoy `orderBy('created_at', 'desc')`)
- `resources/js/Pages/Cars/Kanban.vue` + `CarKanbanController`
- Cualquier otro listado de `Car` que muestre `purchase_price`
