<!-- filepath: docs/MEJORAS_MARKETPLACE_2026-08-06.md -->
# Plan de Mejoras del Marketplace Público
**Fecha:** 2026-08-06
**Aplica a:** `resources/js/Pages/Public/Marketplace*.vue` y `CarRequestForm.vue`
**Horizonte:** 4-8 semanas, priorizado por impacto / esfuerzo

> El marketplace público es el activo más estratégico del producto. Es lo único que ve un visitante anónimo. Cualquier fricción ahí se traduce en una solicitud perdida.
> Este plan recoge las 9 mejoras acordadas y añade criterios de aceptación, estimaciones y dependencias.

---

## 🎯 Supuestos de producto (acordados con Carra)

- **No hay rotación de stock**: cada coche es único, son **ofertas de importación**, no ventas de lote. Aplicar presión con "quedan 2 unidades" o "visto por 12 personas" sería contraproducente y poco creíble.
  - **Descartado:** countdown, "low stock", urgency.
- **Las stats del hero son claims de marca**, no KPIs operativos. "9 puntos de investigación", "100% transparente" son promesa de servicio, no métricas de la BD.
  - **Descartado:** stats dinámicas desde BD. Se mantienen los claims hardcoded.
- **El catálogo es público pero las solicitudes de vehículo son la conversión real**. Cada click en "Ver informe" o "Pedir que te avisen" debe facilitar el camino al formulario.

---

## 📊 Resumen ejecutivo

| # | Mejora | Esfuerzo | Impacto conversión | Estado |
|---|---|---|---|---|
| 1 | Alinear obligatoriedad backend ↔ frontend | 2h | Alto (UX) | Pendiente |
| 2 | Filtros extendidos (año, combustible, cambio, carrocería) | 3h | Alto | Pendiente |
| 3 | Sticky filter bar en scroll | 2h | Alto | Pendiente |
| 4 | Botón WhatsApp flotante con mensaje pre-rellenado | 1.5h | Alto | Pendiente |
| 5 | Compartir coche (WhatsApp / email / copiar enlace) | 1.5h | Medio (viralidad) | Pendiente |
| 6 | Galería / lightbox de fotos en `MarketplaceShow` | 3h | Alto | Pendiente |
| 7 | Contador de visitas en la ficha + mostrar en la card | 2h | Bajo (métrica) | Pendiente |
| 8 | Vista comparativa (checkbox → modal con tabla) | 4h | Alto | Pendiente |
| 9 | Wishlist con `localStorage` (sin login) | 4h | Alto (retorno) | Pendiente |
| 10 | Búsqueda server-side con URL compartible | 6h | Alto (SEO + share) | Pendiente |
| 11 | Testimonios reales (sección + CRUD admin) | 8h | Alto (social proof) | Pendiente |
| 12 | Newsletter popup suave con lead magnet | 3h | Medio | Pendiente |
| 13 | Schema.org `Vehicle` + `Offer` en fichas | 2h | Alto (SEO rich snippets) | Pendiente |
| 14 | OG meta tags dinámicos por coche | 2h | Alto (sharing) | Pendiente |
| 15 | Calculadora de financiación en la ficha | 4h | Alto | Pendiente |

**Total estimado:** ~50 horas ≈ 1.5 semanas de trabajo concentrado. Recomendable trocear en 3 iteraciones.

---

## 🔧 Detalle por mejora

### 1. Alinear obligatoriedad backend ↔ frontend

**Problema actual:** El backend exige 13 campos como `required`, el frontend marca 14 con asterisco y HTML `required` en `name` está ausente. Esto causa:
- Fricción: el usuario rellena un campo "obligatorio" y el form falla con "este campo es required" porque coincide con otro.
- Confusión: el asterisco sugiere obligatoriedad pero el backend la exige de forma diferente.

**Solución:**
1. Crear un único array de "campos obligatorios" en el frontend que coincida 1:1 con el backend.
2. Renderizar el asterisco rojo solo si la clave está en el array.
3. Añadir/quitar `required` HTML desde el mismo array.
4. Backend: refactorizar el `Validator::make` para usar el array en lugar de strings repetidos.

**Criterio de aceptación:**
- Carga el formulario, envía con solo `name` + `phone` (mínimo absoluto) → 200 OK.
- Marca con `*` solo los campos del array.
- Backend rechaza con mensaje en español claro si falta un campo obligatorio.

**Archivos:** `app/Http/Controllers/PublicCarRequestController.php`, `resources/js/Pages/Public/CarRequestForm.vue`.

---

### 2. Filtros extendidos (año, combustible, cambio, carrocería)

**Estado actual:** Solo búsqueda libre + rango de presupuesto + km máximo + chips de marca + chip de buen precio.

**Solución:**
- Añadir selects: año min/max (reutilizar `yearOptions`), combustible, cambio, carrocería, plazas.
- Mantener el patrón client-side (`computed` `filteredCars`) por ahora; migrar a server-side en mejora #10.
- Chips de combustible/cambio/carrocería debajo de los de marca.

**Criterio de aceptación:**
- Aplicar "Diésel + 2018-2022 + SUV" → solo aparecen coches con esos atributos.
- Reset de filtros con un botón "Limpiar" que aparece solo si hay alguno activo.

**Archivos:** `resources/js/Pages/Public/MarketplaceIndex.vue`.

---

### 3. Sticky filter bar en scroll

**Problema:** El catálogo puede crecer y el usuario pierde los filtros al bajar.

**Solución:**
- Cuando el `<div>` de filtros originales sale del viewport, aparece una versión compacta sticky en la parte superior (justo debajo del header público).
- Misma lógica de filtrado, en vivo.
- Botón "Ver catálogo" para hacer scroll de vuelta a los resultados.

**Criterio de aceptación:**
- Scroll > 400px → aparece barra sticky con los filtros más usados (search + presupuesto).
- Click en filtro sticky → también actualiza los resultados en vivo.

**Archivos:** `resources/js/Pages/Public/MarketplaceIndex.vue` (extraer a componente `FilterBar.vue` si crece).

---

### 4. Botón WhatsApp flotante con mensaje pre-rellenado

**Problema:** El CTA "Contactar" del header apunta a la sección `#contacto` que solo está en la home. En `MarketplaceShow` no hay WhatsApp.

**Solución:**
- Botón flotante fijo en bottom-right con icono de WhatsApp.
- En `MarketplaceShow`: mensaje pre-rellenado `Hola, estoy interesado en el {{brand}} {{model}} (ref. {{id}}) que he visto en vuestra web. ¿Sigue disponible?`
- En `MarketplaceIndex`: mensaje genérico `Hola, me interesa uno de los coches de vuestro catálogo. ¿Podéis darme más info?`

**Criterio de aceptación:**
- Click → abre `wa.me/34675701439?text=...` en pestaña nueva.
- Botón oculto en impresión / no aparece en mobile superpuesto al folleto (mover folleto a top o cambiar posición).

**Archivos:** `resources/js/Components/WhatsappButton.vue` (nuevo, reusable), `MarketplaceIndex.vue`, `MarketplaceShow.vue`.

---

### 5. Compartir coche (WhatsApp / email / copiar enlace)

**Problema:** El cliente no puede compartir un coche que le gusta sin copiar la URL a mano.

**Solución:**
- En la ficha del coche, debajo del título, tres botones pequeños: WhatsApp, Email, Copiar.
- `Compartir por WhatsApp`: `?text=Mira este {{brand}} {{model}}: {{url}}`
- `Email`: `mailto:?subject=...&body=...`
- `Copiar`: usa `navigator.clipboard.writeText(url)` con toast "Enlace copiado".

**Criterio de aceptación:**
- Cada botón funciona en su canal.
- "Copiar" muestra un toast 2s y cambia el icono a un check.

**Archivos:** `resources/js/Components/ShareButtons.vue` (nuevo), `MarketplaceShow.vue`.

---

### 6. Galería / lightbox de fotos en `MarketplaceShow`

**Problema:** Solo se muestra la primera foto. Si hay 10 fotos, no se ven.

**Solución:**
- Reemplazar la `<img>` actual por un componente `Gallery.vue` que muestre:
  - Foto principal grande.
  - Thumbnails debajo (horizontal scrollable en mobile).
  - Click en cualquier thumbnail o en la principal → abre un lightbox a pantalla completa con:
    - Navegación con flechas (←/→), swipe en touch, Esc para cerrar.
    - Contador "3 / 8" arriba a la derecha.
    - Fondo `bg-asphalt-900/95` con backdrop-blur.

**Criterio de aceptación:**
- Ver un coche con 5+ fotos.
- Navegación con teclado funciona.
- Lightbox no rompe el scroll de la página al cerrarse (`body overflow:hidden` mientras abierto).

**Archivos:** `resources/js/Components/Gallery.vue` (nuevo), `MarketplaceShow.vue`.

---

### 7. Contador de visitas en la ficha + mostrar en la card

**Problema:** No sabemos qué coches generan más interés.

**Solución:**
- Migración: añadir `view_count` (unsignedInteger, default 0) a `cars`.
- En `MarketplaceController@show`: incrementar atómicamente en la consulta.
- En la card del catálogo: badge discreto con icono `EyeIcon` y el nº de visitas (ej. "visto 23 veces"). Solo si `view_count > 0`.

**Criterio de aceptación:**
- Visitar la ficha de un coche 3 veces → `view_count = 3`.
- La card muestra el badge en `MarketplaceIndex`.
- Privacidad: no mostrar el contador exacto si es 1 (demasiado granular, parece vanity).

**Archivos:** migration nueva, `app/Models/Car.php`, `app/Http/Controllers/PublicMarketplaceController.php`, `MarketplaceIndex.vue`.

**Cuidado:** no aplicar a usuarios autenticados como "owner" o "admin" (falsea la métrica). En el controller, `if (auth()->check() && auth()->user()->organization_id === $car->organization_id) return;`.

---

### 8. Vista comparativa (checkbox → modal con tabla)

**Problema:** El cliente quiere comparar 2-3 coches pero tiene que abrir 3 pestañas.

**Solución:**
- En cada card del catálogo, un checkbox "Comparar" arriba a la izquierda (al lado del badge de verdict).
- Cuando hay 2-3 coches seleccionados → aparece una barra sticky inferior con "Comparar 2 coches" + botón "Limpiar".
- Click en "Comparar" → modal full-screen con tabla:
  - Filas: foto, brand+model, año, km, combustible, cambio, precio, ahorro estimado, ciudad, link al detalle.
  - Columnas: cada coche seleccionado.
  - En cada fila, marcar con `bg-emerald-50` el mejor valor (ej. menor precio, menor km).

**Criterio de aceptación:**
- Seleccionar 3 coches → la barra muestra "Comparar 3 coches".
- Click → modal abre con los 3 en columnas.
- Cerrar modal → la selección persiste hasta que el usuario limpie o navegue a otra página.

**Archivos:** `resources/js/Components/CompareModal.vue` (nuevo), `MarketplaceIndex.vue`, store reactivo en `MarketplaceIndex.vue` (no necesita Pinia para esto).

---

### 9. Wishlist con `localStorage` (sin login)

**Problema:** El cliente ve un coche que le gusta pero no quiere enviar la solicitud hoy. Lo pierde.

**Solución:**
- Icono `HeartIcon` en cada card (esquina superior derecha, junto al badge de semáforo).
- Click → toggle. Guardar en `localStorage.jj_wishlist = JSON.stringify([{id, brand, model, price, added_at}])`.
- Página `/wishlist` (privada solo para el cliente) → muestra los coches guardados, con CTA "Enviar solicitud con estos coches".
- Botón flotante "Ver wishlist (n)" abajo a la izquierda, solo si hay items.

**Criterio de aceptación:**
- Persiste entre sesiones del mismo navegador.
- Si un coche guardado se vende (`is_sold` o `status != 'Delivered'`), mostrar badge "No disponible" pero mantenerlo en la lista con tachado.
- "Enviar solicitud con estos" → redirige al form `CarRequestForm` con el campo `requirements` pre-rellenado: `Estoy interesado en: BMW Serie 3 (ref 12), Audi A4 (ref 14).`

**Archivos:** `resources/js/Composables/useWishlist.js` (nuevo, con API `add/remove/list/clear`), `resources/js/Components/WishlistButton.vue`, `MarketplaceIndex.vue`, `MarketplaceShow.vue`, `Pages/Public/Wishlist.vue` (nuevo), ruta `GET /wishlist` en `routes/web.php`.

---

## 🚀 Mejoras de servidor (no en `Resources/js/Pages`)

### 10. Búsqueda server-side con URL compartible

**Problema:** Filtros client-side no escalan más allá de ~50 coches. Y no se pueden compartir URLs filtradas.

**Solución:**
- `PublicMarketplaceController@index` aplica filtros desde `request()->only(['search', 'brand', 'min_price', 'max_price', 'mileage', 'year_min', 'year_max', 'fuel', 'transmission', 'body_type', 'seats'])`.
- Usa `paginate(12)` y `withQueryString()`.
- La vista pasa `filters` con los valores actuales para pre-rellenar el form.
- Reemplaza el `computed filteredCars` por paginación real.

**Criterio de aceptación:**
- `https://...marketplace?brand=BMW&min_price=10000` muestra solo BMW con precio ≥ 10k.
- Compartir esa URL → otra persona ve el mismo resultado.
- Paginación con `?page=2` funciona.
- Si el catálogo tiene <50 coches, el comportamiento es indistinguible del client-side.

**Archivos:** `PublicMarketplaceController.php`, `MarketplaceIndex.vue` (quitar `filteredCars`, consumir `cars.links`).

---

### 11. Testimonios reales (sección + CRUD admin)

**Problema:** El marketplace tiene trust signals genéricos pero no hay voz de clientes reales.

**Solución:**
- Migración: `testimonials` (id, organization_id, client_id, text, rating 1-5, photo_path, is_public, created_at).
- CRUD admin: `app/Http/Controllers/TestimonialController.php` + `Pages/Testimonials/{Index,Edit}.vue`.
- Sección pública en `MarketplaceIndex` después de "How it works":
  - Grid de 3 cards con texto, nombre (o "Cliente verificado"), estrellas, foto opcional.
  - Si hay <3 testimonios, no mostrar la sección.

**Criterio de aceptación:**
- Admin crea testimonio, marca como público.
- Aparece en la home pública en orden cronológico inverso.
- Si un testimonio es de un cliente vinculado a un coche, opcionalmente enlazar.

**Archivos:** migration, model, controller, vistas admin, sección en `MarketplaceIndex.vue`.

---

### 12. Newsletter popup suave con lead magnet

**Problema:** Capturar emails de visitantes que no llegan a enviar solicitud.

**Solución:**
- Componente `NewsletterPopup.vue` que aparece cuando:
  - Han pasado 30s desde la carga, O
  - El usuario ha hecho scroll >50% de la página, O
  - Intenta salir de la página (`mouseleave` en `body`, desktop only).
- Ofrece lead magnet: "Guía gratuita: 7 cosas que debes saber antes de importar un coche de Alemania" (PDF en `public/`).
- Form: solo email. POST a `newsletter/subscribe` que guarda en `newsletter_subscribers` (tabla nueva).
- Una vez suscrito, no vuelve a aparecer (cookie 30 días).

**Criterio de aceptación:**
- Aparece solo una vez por sesión/navegador.
- Email se guarda en BD y se muestra en admin.
- Cierre (X) y "No, gracias" funcionan.

**Archivos:** `Components/NewsletterPopup.vue`, controller + tabla, `MarketplaceIndex.vue`.

---

### 13. Schema.org `Vehicle` + `Offer` en fichas

**Problema:** Google no muestra rich snippets con nuestros coches (precio, km, etc.).

**Solución:**
- En `MarketplaceShow.vue`, dentro del `<Head>`, añadir `<script type="application/ld+json">` con:
```json
{
  "@context": "https://schema.org",
  "@type": "Vehicle",
  "name": "BMW Serie 3 2020",
  "brand": "BMW",
  "model": "Serie 3",
  "vehicleModelDate": "2020",
  "mileageFromOdometer": { "@type": "QuantitativeValue", "value": 50000, "unitCode": "KMT" },
  "fuelType": "Diesel",
  "vehicleTransmission": "Manual",
  "offers": {
    "@type": "Offer",
    "price": 18500,
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock"
  }
}
```

**Criterio de aceptación:**
- Validar con https://validator.schema.org/ → 0 errores.
- En Google Search Console → "Mejoras" → "Vehículos" aparecen las fichas.

**Archivos:** `MarketplaceShow.vue` (computed `schemaOrg`).

---

### 14. OG meta tags dinámicos por coche

**Problema:** Al compartir por WhatsApp/Twitter, el preview es genérico.

**Solución:**
- En `MarketplaceShow.vue` `<Head>`:
  - `<meta property="og:title" content="${brand} ${model} - ${price}€">`
  - `<meta property="og:description" content="${year} · ${km}km · ${fuel} · ${transmission}">`
  - `<meta property="og:image" content="${first_photo_url}">`
  - `<meta property="og:type" content="product">`
  - `<meta name="twitter:card" content="summary_large_image">`

**Criterio de aceptación:**
- Compartir URL en WhatsApp → preview con foto, título, precio.
- Validar con https://www.opengraph.xyz/

**Archivos:** `MarketplaceShow.vue` `<Head>`.

---

### 15. Calculadora de financiación en la ficha

**Problema:** El cliente quiere saber "cuánto me costaría al mes" antes de pedir info.

**Solución:**
- Bloque nuevo en `MarketplaceShow.vue`:
  - Precio del coche (input editable, pre-relleno con `purchase_price`).
  - Entrada (slider, default 0%, max 50%).
  - Plazo (12/24/36/48/60/72/84 meses, default 60).
  - TIN anual (default 7.99%, editable).
  - Resultado: cuota mensual, total a pagar, intereses.
- Fórmula: cuota = capital × (i × (1+i)^n) / ((1+i)^n − 1), donde i = TIN/12, n = meses.
- Disclaimer: "Cálculo orientativo, sujeto a aprobación bancaria."

**Criterio de aceptación:**
- Cambiar entrada o plazo → recalcula en tiempo real.
- El cálculo es correcto (verificar con 2 ejemplos conocidos).

**Archivos:** `resources/js/Components/FinancingCalculator.vue` (nuevo), `MarketplaceShow.vue`.

---

## 🛣️ Roadmap sugerido

### Iteración 1 (semana 1-2) — Quick wins visibles
- #1 Alinear obligatoriedad
- #2 Filtros extendidos
- #3 Sticky filter bar
- #4 WhatsApp flotante
- #5 Compartir coche

> **Resultado esperado:** UX básica pulida, conversión medible.

### Iteración 2 (semana 3-4) — Engagement
- #6 Galería / lightbox
- #7 Contador visitas
- #8 Vista comparativa
- #9 Wishlist localStorage
- #14 OG meta tags

> **Resultado esperado:** visitante interactúa más, vuelve, comparte.

### Iteración 3 (semana 5-8) — Server-side + growth
- #10 Búsqueda server-side (más profundo por SEO)
- #11 Testimonios
- #12 Newsletter popup
- #13 Schema.org
- #15 Calculadora financiación

> **Resultado esperado:** SEO rico, lead capture, calculadora de cierre.

---

## 🧪 Métricas para validar éxito

| Métrica | Baseline | Target 2m | Target 4m |
|---|---|---|---|
| Solicitudes / mes | n/a | +30% | +60% |
| Tiempo medio en `/marketplace` | n/a | +40% | +80% |
| Coches vistos por sesión | n/a | +50% | +100% |
| Wishlist adds / sesión | n/a | >0.5 | >1.0 |
| Comparativas iniciadas / mes | n/a | >20 | >100 |
| Newsletter subs / mes | n/a | >30 | >200 |
| Búsquedas con URL compartible | n/a | >10/mes | >100/mes |
| Rich snippets en Search Console | 0% | >50% | 100% |
| Calculadora: cuota mensual clicked → form | n/a | >5% | >15% |

---

## 🚫 Lo que NO entra en este plan (acordado)

- **Stock / countdown / "visto por X"** — son servicios de importación, no ventas. Aplicar presión de escasez sería contraproducente.
- **Stats dinámicas desde BD** — las 3 stats del hero (`9 / 100% / 🇪🇸`) son claims de marca, no KPIs.
- **CMS headless** — el contenido de las páginas se mantiene en Inertia/Vue. Si crece mucho, reevaluar en 6 meses.
- **A/B testing desde día 1** — esperar >1000 visitas/mes para tener significancia estadística.
- **PWA / Service Worker / push** — el flujo actual de email cubre los retornos; no es core.
- **AI suggest** — sin datos de uso real, malinterpreta. Reevaluar tras 3 meses con datos.

---

## ✅ Próximo paso

Recomiendo empezar por la **Iteración 1** (#1 a #5) en orden. Son las más rápidas y las que más fricción eliminan. Si me dices "vamos", las implemento en una sesión.

¿Quieres que empiece con #1 (alinear obligatoriedad) o prefieres otro orden?