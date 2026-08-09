# 10 — Schema.org + Open Graph por coche

Son dos tecnologías distintas que resuelven dos problemas distintos. Van juntas en las guías porque ambas viven en el `<head>` / HTML de la página.

---

## Parte 1: Open Graph (OG) — "la tarjeta al compartir"

### El problema

Alguien comparte la URL de un coche por WhatsApp. Sin OG tags, WhatsApp muestra:

```
jjimportmotors.com/marketplace/bmw-serie-3
🔗 (nada más, link pelado)
```

Nadie hace click en eso.

### La solución

Meta tags en el HTML que dicen "si me comparten, muestra ESTO":

```html
<meta property="og:title" content="BMW Serie 3 2020 — 18.500€">
<meta property="og:description" content="50.000 km · Diésel · Manual · Verificado">
<meta property="og:image" content="https://.../foto-principal.jpg">
<meta property="og:type" content="product">
<meta name="twitter:card" content="summary_large_image">
```

Ahora al compartir aparece: **foto grande del coche + título + precio**. Eso es un anuncio gratis en cada WhatsApp.

### Cómo está hecho aquí

En `MarketplaceShow.vue` (commit `0f95063`), los valores se **computan desde los datos del coche** (`car.brand`, `car.purchase_price`, primera foto...) y se inyectan en el `<Head>` de Inertia. No hay que escribir tags a mano por coche: es un template que se rellena solo.

### Cómo probarlo

Pega cualquier URL en [opengraph.xyz](https://www.opengraph.xyz) → ves exactamente la tarjeta que verá el receptor.

---

## Parte 2: Schema.org (JSON-LD) — "el idioma de Google"

### El problema

Google lee tu página y ve TEXTO: "BMW Serie 3, 18.500€, 50.000km". Tiene que **adivinar** que 18.500 es el precio y no el kilometraje. A veces acierta, a veces no.

### La solución

Un bloque JSON dentro del HTML que **etiqueta cada dato con su significado**, usando un vocabulario estándar mundial (schema.org, mantenido por Google/Bing/Yahoo):

```json
{
  "@context": "https://schema.org",
  "@type": "Vehicle",
  "name": "BMW Serie 3 2020",
  "brand": "BMW",
  "mileageFromOdometer": { "@type": "QuantitativeValue", "value": 50000, "unitCode": "KMT" },
  "fuelType": "Diesel",
  "offers": {
    "@type": "Offer",
    "price": 18500,
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock"
  }
}
```

Ahora Google **no adivina**: sabe que es un vehículo, su precio exacto, su divisa, su disponibilidad.

### ¿Qué gano con eso? Los rich snippets

Los resultados "enriquecidos" de Google (los que tienen foto, precio, estrellas DENTRO de la lista de búsqueda) se alimentan de este JSON. Un resultado con precio visible recibe hasta **30% más clicks** que el link azul de siempre. Mismo posicionamiento, más tráfico.

### Los dos schemas que usamos

| Tipo | Dónde | Qué declara |
|---|---|---|
| `AutoDealer` | Global (layout) | "Esta web es un concesionario/importador" → ficha de negocio |
| `Vehicle` + `Offer` | Cada ficha de coche | "Esta página es ESTE coche a ESTE precio" → rich snippet de producto |

📁 Implementación: `resources/views/partials/schema-org.blade.php` (global) + computed `schemaOrg` en `MarketplaceShow.vue` (por coche).

⚠️ **Trampa real que ocurrió aquí:** en Blade, `@context` y `@type` se interpretaban como directivas de Blade y rompían el JSON. Fix (commit `ff9fbd1`): escapar con `@@context`. Si algún día escribes JSON-LD en Blade, recuerda esto.

### Cómo validarlo

[validator.schema.org](https://validator.schema.org) → pega la URL → 0 errores = correcto. Y en **Google Search Console** → sección "Mejoras" → ves cuántas páginas con rich snippets tienes indexadas.

---

## La diferencia resumida

| | Open Graph | Schema.org |
|---|---|---|
| **¿Quién lo lee?** | WhatsApp, Twitter/X, LinkedIn, Telegram | Google, Bing |
| **¿Qué produce?** | Tarjeta bonita al compartir | Rich snippets en búsqueda |
| **Formato** | `<meta property="og:*">` | `<script type="application/ld+json">` |
| **Beneficio** | Más clicks en redes/mensajería | Más clicks desde Google |

> **Regla reutilizable:** Toda página pública de "entidad concreta" (producto, coche, inmueble, evento, receta) necesita ambas: OG para cuando la gente la comparte, Schema para cuando Google la lista. Son 1-2 horas de trabajo una vez (son templates que se rellenan solos) y pagan tráfico gratis para siempre.
