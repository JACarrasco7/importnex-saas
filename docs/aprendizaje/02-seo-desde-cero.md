# 02 — SEO desde cero (para programadores)

## ¿Qué es el SEO, sin humo?

Google es un programa que hace 3 cosas:

```
1. RASTREAR (crawl)  → un bot visita tus URLs
2. INDEXAR           → guarda el contenido en su "base de datos"
3. RANKEAR           → decide en qué posición apareces para cada búsqueda
```

Tu trabajo como programador es facilitarle los pasos 1 y 2. El 3 depende de contenido, autoridad y tiempo.

## Las 4 piezas técnicas que implementamos (y por qué)

### 1. `robots.txt` — "la puerta de entrada"

**Qué es:** un archivo de texto en `/robots.txt` que le dice al bot qué puede y no puede visitar.

```
User-agent: *
Disallow: /dashboard
Disallow: /billing
Allow: /marketplace
Sitemap: https://tudominio.com/sitemap.xml
```

**Por qué:** sin él, Google pierde tiempo rastreando tu login, tu dashboard... y puede indexar páginas privadas. Le ahorras "presupuesto de rastreo" (Google no rastrea infinitas páginas por sitio).

📁 En este repo: [public/robots.txt](../../public/robots.txt)

### 2. `sitemap.xml` — "el índice del libro"

**Qué es:** un XML que lista TODAS las URLs públicas que quieres que Google conozca, con cuándo cambiaron.

```xml
<url>
  <loc>https://jjimportmotors.com/marketplace/bmw-serie-3</loc>
  <lastmod>2026-08-08</lastmod>
</url>
```

**Por qué:** sin sitemap, Google descubre páginas siguiendo enlaces. Un coche que nadie enlaza = invisible. Con sitemap, le entregas la lista completa.

📁 Aquí es **dinámico**: `SitemapController` genera el XML desde la BD (solo coches con `is_marketplace=true`), con **cache de 1 hora** para no consultar la BD en cada visita del bot.

👉 Guía completa en [12-sitemap-y-ci.md](12-sitemap-y-ci.md).

### 3. Meta tags OG/Twitter — "la tarjeta de presentación"

Cuando alguien pega tu URL en WhatsApp/Twitter, aparece una tarjeta con foto y texto. Eso NO es magia: son meta tags en el `<head>`:

```html
<meta property="og:title" content="BMW Serie 3 2020 - 18.500€">
<meta property="og:image" content="https://.../foto-coche.jpg">
```

**Por qué:** una URL compartida con foto+precio recibe ~2-3x más clicks que un link pelado. Es "SEO social": cada compartido es un lead gratis.

### 4. Schema.org (JSON-LD) — "hablar en el idioma de Google"

Es un bloque JSON en el HTML que describe **qué es** la página en vocabulario estándar:

```json
{ "@type": "Vehicle", "brand": "BMW", "offers": { "price": 18500 } }
```

**Por qué:** Google lo usa para los **rich snippets** (esos resultados con estrellas, precio, foto directamente en la lista de búsqueda). Más espacio visual = más clicks.

👉 Detalle en [10-schema-y-og.md](10-schema-y-og.md).

## Lo que NO hicimos (y por qué)

- ❌ **SSR complejo / pre-rendering**: Inertia renderiza en cliente, pero los meta tags críticos van en el HTML del servidor (`app.blade.php` + props). Suficiente para Google (su bot ejecuta JS).
- ❌ **Blog de contenidos**: es la siguiente palanca SEO real (contenido = keywords), pero requiere escribir contenido, no código.
- ❌ **Pagar por backlinks**: fuera de scope técnico.

## El porqué (lo que te llevas)

> **Regla reutilizable:** El SEO técnico es un checklist finito: `robots.txt` + `sitemap.xml` + meta OG + Schema.org + URLs limpias + web rápida. Son ~2 días de trabajo y se hacen UNA vez. El 80% del SEO después es contenido, no código.

### Checklist para tu próxima app
- [ ] `/robots.txt` con sitemap referenciado
- [ ] `/sitemap.xml` dinámico (generado desde BD, con cache)
- [ ] OG tags por página pública (mínimo: title, description, image)
- [ ] JSON-LD del tipo de negocio (`AutoDealer`, `Product`, `LocalBusiness`...)
- [ ] Validar con: [validator.schema.org](https://validator.schema.org) y [opengraph.xyz](https://www.opengraph.xyz)
- [ ] Registrar el dominio en **Google Search Console** (gratis) → ahí ves qué indexa Google y qué errores encuentra
