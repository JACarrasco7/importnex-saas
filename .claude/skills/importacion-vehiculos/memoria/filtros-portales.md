# Filtros por portal — qué funciona y cómo

> **Memoria de filtros/URLs verificados por portal.** Objetivo: construir el plan de fase sabiendo ANTES qué filtros aplican por URL vs solo por clic, sin probar a ciegas (ahorra peticiones).
>
> **Fuente:** `../memoria/retrospectiva.md` (prueba Playwright 12-ago) + `../02-flujos/paginas_reales.md` + `trampas-encontradas.md`.
>
> **Formato de cada entrada:**

```markdown
### <Portal> · <verificación: fecha>
- **Qué aplica por URL:** <parámetros exactos>
- **Qué NO aplica por URL (solo clic):** <parámetros>
- **Trucos verificados:** <comportamiento clave>
- **Advertencias:** <trampas>
```

---

## 🌍 mobile.de · verificado 2026-08-12 (VS Code Playwright)

- **Qué aplica por URL:** pocos filtros con `q=`. El listado sale SIN filtrar si usas `q` + parámetros (`ps`, `kmmax`, `ezmin`) → NO fiarse del contador.
- **Qué NO aplica por URL (solo clic):** `Kraftstoffart` (Benzin/Diesel) · acordeones de filtros (expandir clic). El clic en `label Benzin` NO activa el filtro; los acordeones `Kraftstoffart Beliebig ändern` no se expanden con selectores estándar.
- **Trucos verificados:**
  - Banner de cookies: cerrar con `button.mde-consent-accept-btn` + `force:true` + quitar `[class*=consent]` del DOM (el overlay persiste si no se fuerza).
  - Doble pasada por **kW** (campo estructurado `Leistung` von/bis) para topes de gama mal etiquetados — NO falla como la variante de texto.
  - Orden preferido: mobile.de directo → AutoScout24 directo → AutoUncle → kleinanzeigen.
- **Advertencias:** NUNCA >45 peticiones/sesión (aviso a 35). Clic posicional sobre screenshot (Desktop) es el método robusto.

## 🇪🇸 Coches.net · verificado 2026-08-12 (VS Code Playwright)

- **Qué aplica por URL:** `minyear` · `maxkm` · `minpower` (sí filtran: contador 259k→159k).
- **Qué NO aplica por URL (solo clic):** filtro de MARCA/MODELO (no aplica por URL). Los filtros laterales no son `<button>` estándar; el typeahead del buscador no muestra modelos con Playwright.
- **Trucos verificados:**
  - Paginación por `pagina=N` (verificado 15-ago) + `nextToken=` (cursor de la API interna, método degradado).
  - CV en la tarjeta (`116 cv`) → doble pasada por **Potencia** (en CV) además de búsqueda por texto para topes de gama.
  - Parámetros reales: `anoh` · `cajacambio` · `engineHpTo` · `fuels` · `hasta` · `kilometersTo` · `puertas` · `orden`.
- **Advertencias:** el filtro de marca/modelo requiere navegación real (Desktop).

## 🇪🇸 Milanuncios · verificado 2026-08-15

- **Trucos verificados:** paginación por URL (confirmado 15-ago). Fuente de chollos particulares (precio negociable).

## 🏷️ Wallapop · patrón general

- **Rol:** chollos particulares ES · precio negociable · compra nacional.
- **Advertencias:** verificar estado real del anuncio (muchos vendidos/duplicados).

## 🏷️ AutoScout24.de · patrón general

- **Rol:** CONTAR oferta DE, NUNCA precio (agrega feeds sin cribar → anuncios engañosos, A8).
- **Trucos:** solo para N de ofertas, no para mediana ni veredicto.

## 🏷️ AutoUncle · patrón general

- **Rol:** rotación DE (días publicado) — SOLO contar, NO referencia de precio (agregador).
- **Advertencias:** se puede omitir en Fase 1 si hay hueco claro (declarado).

## 🏷️ kleinanzeigen.de · patrón general

- **Rol:** chollos particulares DE · precio + VB (negociable).
- **Pendiente:** extractor propio (ROADMAP).

---

## 🧭 Cómo usar esto en el plan de fase

1. **Filtros por URL** → se ponen en la URL directa de la búsqueda (Fase 1 barata).
2. **Filtros solo por clic** → se hacen con navegación real (Desktop), clic posicional sobre screenshot.
3. **Doble pasada** → activar si el modelo es tope de gama (OPC/GTI/R/M/AMG/RS/Type R/N/Performance): 2ª búsqueda por kW/CV.
4. **País DE** → mobile.de directo primero, NUNCA saltar (A2).
5. **Cobertura 7 fuentes** → tabla en `../SKILL.md` §ARRANQUE OBLIGATORIO.

## 🔄 Verificación

- **2026-08-16:** archivo creado. Datos extraídos de retrospectiva 12-ago (Playwright) + paginas_reales + trampas.
- **Actualizar** cada vez que se verifique un filtro nuevo en un portal (fecha + parámetro).
