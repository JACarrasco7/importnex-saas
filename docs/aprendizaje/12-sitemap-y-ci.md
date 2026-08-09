# 12 — Sitemap.xml + CI con GitHub Actions

Son dos temas distintos. Los dos son "infraestructura invisible" que trabaja sola una vez montada.

---

# PARTE A — Sitemap.xml

## ¿Qué es?

Un archivo XML en `/sitemap.xml` que lista **todas las URLs públicas** de tu web, con metadatos para los buscadores:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://jjimportmotors.com/</loc>
    <lastmod>2026-08-08</lastmod>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://jjimportmotors.com/marketplace/bmw-serie-3-2020</loc>
    <lastmod>2026-08-07</lastmod>
    <changefreq>daily</changefreq>
  </url>
</urlset>
```

| Campo | Significa |
|---|---|
| `loc` | La URL |
| `lastmod` | Última modificación → Google prioriza re-rastrear lo reciente |
| `changefreq` | Pista de cada cuánto cambia |
| `priority` | Qué páginas son más importantes (0.0–1.0) |

## ¿Para qué sirve?

Sin sitemap, Google descubre tus páginas **siguiendo enlaces**. Problema: un coche publicado ayer al que nadie enlaza desde fuera tarda días/semanas en ser descubierto. Con sitemap, le entregas la lista completa en bandeja → indexación en horas.

Analogía: sin sitemap, Google es un turista explorando tu ciudad callejeando. Con sitemap, le das el plano con todo marcado.

## ¿Cómo se hizo aquí? (y por qué cada decisión)

**Decisión 1: dinámico, no estático.** Un `sitemap.xml` generado a mano queda obsoleto al publicar el siguiente coche. Aquí lo genera `SitemapController@index` consultando la BD: solo coches con `is_marketplace = true` (los públicos). Cada coche nuevo aparece solo; cada coche vendido desaparece solo.

**Decisión 2: cache de 1 hora.** El bot de Google puede pedir el sitemap muchas veces. Sin cache, cada petición = 1 query a la BD. Con cache 1h, la BD se toca 24 veces al día en el peor caso → **3600x menos carga** que una query por petición de bot.

**Decisión 3: flush automático con Observer.** Cuando un coche cambia `is_marketplace` (se publica o se despublica), `CarObserver` borra la cache del sitemap → el siguiente bot ve el estado real. Cache fresco sin cron ni intervención manual.

**Decisión 4: referenciado en robots.txt.** La línea `Sitemap: https://.../sitemap.xml` en `robots.txt` es cómo Google lo encuentra sin que tú hagas nada.

📁 Código: `app/Http/Controllers/SitemapController.php` + ruta `/sitemap.xml` + `CarObserver`. Tests: 8 (XML válido, solo públicos, flush de cache...).

## ¿Cómo se usa después?

1. Registras el dominio en **Google Search Console** (gratis).
2. Le dices "mi sitemap está en /sitemap.xml".
3. A partir de ahí ves: qué páginas ha indexado, qué errores hay, qué búsquedas te traen visitas.

---

# PARTE B — CI con GitHub Actions

## ¿Qué es CI (Integración Continua)?

Un robot que, **cada vez que haces push**, ejecuta automáticamente tus verificaciones en un servidor limpio de GitHub. Si algo falla, te avisa (y puede bloquear el merge).

**El problema que resuelve:** sin CI, "en mi máquina funciona" es la frase más peligrosa del software. Tu máquina tiene tu PHP, tu Node, tu `.env`, tus caches. CI prueba en un entorno virgen = prueba honesta.

## Qué hace nuestro CI ([.github/workflows/ci.yml](../../.github/workflows/ci.yml))

4 jobs (trabajos) que corren en paralelo en cada push:

```
git push
   ↓
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ 1. lint      │ 2. tests     │ 3. i18n      │ 4. build     │
│ Pint: ¿el    │ PHPUnit con  │ ¿es.js y     │ ¿Vite        │
│ código PHP   │ MariaDB real │ en.js tienen │ compila sin  │
│ está bien    │ en un        │ las mismas   │ errores y    │
│ formateado?  │ contenedor?  │ claves?      │ genera dist? │
└──────────────┴──────────────┴──────────────┴──────────────┘
   ↓ todo verde                    ↓ algo rojo
✅ push OK                    ❌ GitHub te avisa en el commit/PR
```

### Por qué estos 4 checks y no otros

| Job | Catástrofe que previene |
|---|---|
| **lint (Pint)** | PRs con guerras de formato, diffs ilegibles |
| **tests** | "He roto el login sin darme cuenta" llegando a producción |
| **i18n parity** | Usuario inglés viendo botones con texto roto `cars.show.title` porque añadiste la clave solo en español |
| **build** | Push con un import roto que peta `npm run build` en Forge a las 23:00 |

### Piezas clave del workflow (para que entiendas el YAML)

```yaml
on: [push, pull_request]        # cuándo se dispara

services:
  mariadb:                      # GitHub levanta una BD real para los tests
    image: mariadb:10.11

steps:
  - uses: actions/checkout@v4   # descarga tu código
  - uses: shivammathur/setup-php@v2
    with:
      php-version: '8.5'        # MISMA versión que tu servidor
  - run: composer install
  - run: php artisan test --compact
```

Y el helper `scripts/sync-missing-keys.cjs`: si el job i18n detecta que falta una clave en `en.js`, puede **auto-rellenarla** con el valor de `es.js` para que el CI pase y luego tú traduzcas bien.

## Lo que cambió en el flujo de trabajo

- **Antes:** hooks pre-commit locales (los 5 checks de `.githooks/`). Problema: se pueden saltar con `--no-verify`.
- **Ahora:** CI en GitHub = el gate está en el servidor, no depende de la disciplina de cada uno. Los hooks locales siguen ahí como primera línea (rápida), el CI como línea definitiva (insobornable).

---

> **Regla reutilizable (sitemap):** si tu app genera contenido público desde BD, el sitemap debe ser dinámico + cacheado + con flush por eventos. Nunca estático.
>
> **Regla reutilizable (CI):** el mínimo CI de cualquier proyecto es: `linter + tests + build` en cada push. Cuesta 30 minutos montarlo la primera vez y te ahorra el primer deploy roto un viernes por la tarde. A partir de ahí, añade checks específicos de TU dominio (aquí: i18n parity).
