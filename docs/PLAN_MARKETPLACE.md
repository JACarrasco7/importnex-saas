# 🚗 Plan — Marketplace para clientes (JJ Import Motors)

> Fecha: 03/08/2026 · Estado: ✅ Implementado y desplegado en producción

## Objetivo

Convertir el marketplace público en una vitrina atractiva y de conversión para clientes potenciales, con control total desde el admin sobre qué coches se publican.

## 1. ✅ Copy corregido (importación = servicio, no hecho)

**Antes (incorrecto):**
> "Solo coches con informe técnico completo: 9 puntos de investigación, comparables de mercado y **trámites de importación resueltos**."

**Ahora (correcto):**
> "Solo coches con informe técnico completo: 9 puntos de investigación y comparables de mercado. **Tú eliges el tuyo y nosotros nos encargamos de la importación llave en mano.**"

La web OFRECE la importación; no es que los coches ya la tengan resuelta. Se actualizó en `resources/js/i18n/es.js` y `en.js` (description, step3_desc, trust_import_desc).

## 2. ✅ Mejora visual del marketplace

- **Hero:** gradiente estoril/platinum con 3 blobs decorativos, CTA con sombra y hover, franja de stats (9 puntos · 100% precio investigado · 🇪🇸 llave en mano).
- **Tarjetas de coche (anuncios premium):**
  - Imagen 16:10 con zoom al hover y overlay oscuro degradado.
  - Badges de veredicto y semáforo.
  - Badge de ahorro (`Ahorra ~X`) si hay `estimated_saving`.
  - Ciudad con pin si hay ubicación.
  - Specs en cajas (Año / Km / Combustible) con iconos.
  - Precio destacado en estoril + botón "Ver informe" que se ilumina al hover.
  - Tarjeta elevada con `hover:-translate-y-1`.
- Colores 100% de marca (estoril/asphalt/platinum), ver `docs/BRAND.md`.

## 3. ✅ Control de publicación por coche (admin)

Nuevo campo `is_marketplace` (boolean) en `cars`:

- **Migración:** `2026_08_03_000001_add_is_marketplace_to_cars.php` (default `false`).
- **Modelo:** `app/Models/Car.php` → fillable + cast booleano.
- **CRUD:** `CarController` store/update incluyen el campo.
- **Admin UI:**
  - `Cars/Edit.vue`: toggle "Publicar en el Marketplace" (switch estoril) en la sección Status & location.
  - `Cars/Index.vue`: badge 🌐 Marketplace en la tarjeta cuando está publicado.
- **Visibilidad pública:** `PublicMarketplaceController` (index y show) exige `is_marketplace = true` además de organización pública, estado `Delivered` y veredicto positivo.

### Reglas de publicación (combinadas)

Un coche aparece en el marketplace SOLO si cumple TODAS:
1. `is_marketplace = true` (toggle del admin) ← NUEVO
2. La organización es pública (`is_public`)
3. Estado = `Delivered`
4. Veredicto en `['Buy', 'Buy if price drops']`

## 4. ✅ Despliegue

- Push a `master` → Forge auto-deploy → migración ejecutada.
- Verificado: columna `is_marketplace` existe en BD de producción.
- Marketplace en: https://jjimportmotors.on-forge.com/marketplace

## Próximos pasos sugeridos

- [ ] Importar/crear coches en producción y marcar los que vayan al marketplace.
- [ ] Revisar en `Cars/Show.vue` si el anuncio interno necesita el toggle también (ya está en Edit/Index).
- [ ] Si se quiere, añadir campo "destacado" (featured) para ordenar coches en el marketplace.
- [ ] Test E2E del flujo admin → publicar → ver en marketplace.
