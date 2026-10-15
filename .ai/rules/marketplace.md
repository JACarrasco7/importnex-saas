---
glob: 'app/Http/Controllers/PublicMarketplaceController.php,resources/js/Pages/Public/**,resources/js/Components/WishlistButton.vue,resources/js/Components/CompareBar.vue,resources/js/Components/FinancingCalculator.vue,resources/js/Composables/useWishlist.js'
title: 'Marketplace público — engagement y viralidad'
---

## Arquitectura (2026-08-07)

Marketplace público accesible sin auth en `/marketplace` y `/marketplace/{car}` con:

- **Filtros server-side**: 12 filtros whitelisted en `PublicMarketplaceController@index` (`FILTER_RULES` constant).
- **Paginación**: 12 coches/página. Backend filtra, NO cliente.
- **Visibilidad**: `is_public=true` (organization) + `is_marketplace=true` (car) + `status NOT IN (Delivered, Discarded)`.
  - **Manda el toggle del operador.** ⚠️ Antes se exigía `status=Delivered` + `verdict IN (Buy, Buy if price drops)`: como `Delivered` es el ÚLTIMO estado del kanban (coche ya entregado al cliente) **y** el default del status es `Located`, la web pública salía SIEMPRE VACÍA (bug detectado 25-sep-2026).
  - Excluir `Delivered`/`Discarded` evita publicar algo ya vendido o descartado; el **veredicto NO filtra** (el operador decide).
  - **Fuente única de verdad:** `Car::scopePublicMarketplace()` — la usan los 4 sitios del `PublicMarketplaceController` (index, filterOptions, compare, show). Antes la condición estaba copiada en 4 sitios y se desincronizó. Para el estado en la ficha: `Car::marketplaceStatus()`.

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

## Politica de contenido en la ficha publica (15-oct-2026)

El usuario confirmo la politica: **la ficha publica NO muestra
informacion negativa del coche** (campo `cons` del modelo, ni
cualquier bloque con tints rojos/naranjas sobre "lo que debes
saber"). La transparencia se vende como valor de MARCA en la home
(seccion "Transparencia total" con 4 pilares), no como dato por
coche. Las contras se las dices al cliente por WhatsApp/llamada
cuando te escribe, en contexto, no en la ficha fria.

**Implementacion:**
- `Car::publicCarPayload()` en `PublicMarketplaceController`: no
  incluye `cons` en el allowlist. Defensa en profundidad: el dato ni
  viaja al navegador.
- `MarketplaceShow.vue` (Vue): el bloque "Puntos en contra" /
  "Lo que debes saber" esta comentado. Renderiza solo "Puntos a
  favor" y la ficha tecnica.
- `car-dossier.blade.php`: solo renderiza "Puntos a favor" del
  dossier. Hay clases CSS `.pc-col.cons` que son dead code (se
  dejaron por si se reactiva la seccion).
- El admin sigue viendo TODO en `Cars/Show` (verdict, red_flags,
  research, cons, notes, etc.). Si algun dia hacen falta contras
  publicas, hay que crear un campo nuevo `cons_public` escrito
  PARA el cliente, no reutilizar `cons`.

**Excepciones que SI se muestran en la ficha publica** (info
objetiva, no opinion interna): `market_min`/`market_max` (rango de
mercado en la barra), `purchase_price` (precio real), `mileage`
(km reales), `year`, `status` como "Disponible" (no el estado
interno `Located`).

## Filtro "Muy buen precio" (15-oct-2026)

El filtro es la UNION de 3 senales (cualquiera basta para entrar
como oferta). NO usar solo `estimated_saving` porque ese campo
esta vacio en muchos coches cuyo ahorro nominal se calcula despues
y se escribe en el bullet de pros:

  a) `estimated_saving >= DEAL_MIN_SAVING` (campo estructurado, 1500)
  b) `purchase_price <= market_avg - DEAL_MIN_SAVING`
  c) `purchase_price <= market_min - DEAL_MIN_SAVING`

Constante `DEAL_MIN_SAVING = 1500` en `PublicMarketplaceController`.
Las claves de cache son constantes (`CACHE_KEY_FILTER_*_V6`) para
poder forzar recálculo subiendo el sufijo, sin esperar el TTL.

## Filtro de color (15-oct-2026)

`Car::COLOR_PALETTE` (17 colores base) + `Car::baseColor()` que
normaliza sin acentos/minusculas y matchea por prefijo. El filtro
muestra solo los colores base, la consulta usa `LIKE "Azul%"` para
encontrar todas las variantes. Pin de paleta en
`CarBaseColorTest::test_palette_has_no_duplicates`.

## Politica de "Como trabajamos" en ficha y dossier (15-oct-2026)

La seccion "Como trabajamos / Somos gestores, no vendedores" debe
listar **lo que SI hacemos**, no un enum de "no vendemos / no
ofrecemos garantia / no hacemos mantenimiento". La primera frase
del cuerpo legal NO puede empezar con un "No" (doble negacion que
suena a descargo).

- `MarketplaceShow.vue` (`legal_body` en `es.js` y `en.js`): empieza
  con "JJ Import Motors no es el vendedor del vehiculo: lo compras
  directamente al vendedor y se matricula a tu nombre." (un unico
  "no" para fijar la regla, luego todo lo que SI hacemos).
- `car-dossier.blade.php`: la seccion `Como trabajamos` tiene dos
  columnas con 5 items cada una, todos en positivo. Sin "No"s.
- El descargo de responsabilidad legal (LSSI-CE/RGPD: no somos
  vendedores, sin garantia, precio sujeto a confirmacion) vive
  SOLO en el footer, en letra pequena. Es obligatorio por ley, pero
  no debe ser lo primero que el cliente lee sobre nosotros.

Ademas, eliminar de la ficha publica cualquier parrafo del estilo
"Esta unidad queda por encima de la media del mercado". Es un dato
interno: si el cliente pregunta por el precio, lo justificamos en
conversacion privada, no en la ficha.

## Badge de estado en el hero del dossier (15-oct-2026)

El estado interno `Located` se traduce como **"Disponible para
gestion"**, no como "Localizado" (que suena a "encontrado por
nuestro equipo" y no a una opcion real para el cliente). Mapeo
completo en `car-dossier.blade.php` → `$estadoLabels`. El resto de
la lista sigue igual (Valuing, Offered, Reserved, etc.) porque
"Reservado para ti" / "En tramites" / "En transito" son
informacion util para el cliente.

## "EL MAS BARATO" / "EL MAS CARO" en barra de mercado (15-oct-2026)

Se mantiene la etiqueta "EL MAS BARATO" en la tarjeta de la barra
de mercado. NO es un veredicto sobre nuestro coche, es el minimo
observado en nuestro barrido del mercado espanol a fecha de
`$fechaDatos`. Lo que SI se elimino: el parrafo "Esta unidad queda
por encima de la media del mercado" que era claramente negativo.
