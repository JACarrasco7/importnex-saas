# Navegación real — MÉTODO PREFERIDO

> Cargar SIEMPRE que haya que investigar portales. Este es el método principal:
> **navegar con tu extensión de navegador como un humano** (computer use:
> screenshot + clic + escribir + scroll). `extractores.md` es el manual técnico
> detallado de esta misma navegación (URLs, mapa visual, diccionario, presupuesto).
> **Antes de navegar un portal, lee `paginas_reales.md`** para saber exactamente
> qué verás en cada captura (estructura capturada 12-ago-2026).
> **Para filtrar/buscar eficiente:** lee `playbook_filtrado.md` (técnicas
> concretas, atajos, detección de chollos).

---

## 🧭 Principios de navegación humana (computer use)

> **Herramientas que tienes (extensión de Claude):** `screenshot` (ver la página),
> `left_click` (clic por coordenadas), `type` (escribir), `key` (atajos: ctrl+l,
> Tab, Enter, Page Down, Escape), `scroll`, `wait`, `zoom`. **No tienes `fetch`
> ni inyección JS** — todo lo que registres debe haberse visto en una captura.
> Detalles en `extractores.md`.
> **⚠️ Compatible con VS Code (12-ago-2026):** el mismo método funciona desde
> el **navegador integrado de VS Code (Playwright)**. El comportamiento es idéntico
> (navegación real, sin fetch/JS injection). Solo cambian los nombres de las herramientas:
>
> | Desktop (extensión) | VS Code (navegador integrado) |
> |---|---|
> | `screenshot` | `screenshot_page` / `read_page` |
> | `left_click` (coordenadas) | `click_element` (selector) |
> | `type` / `key` | `type_in_page` (texto o teclas) |
> | `scroll` / Page Down | scroll de página / `run_playwright_code` |
> | `wait` | automático (espera la carga) |
> | `zoom` | captura con `scrollIntoViewIfNeeded` |
> | `ctrl+l` → URL | `open_browser_page` / `navigate_page` |
>
> **Regla idéntica:** NO usar `fetch` ni inyección JS aunque Playwright lo permita —
> la navegación real es el método preferido por fiabilidad y por el protocolo.
1. **Actúa como un humano.** Abre la URL (`ctrl+l` → pegar → Enter), espera a
   que cargue, lee lo visible, usa los filtros de la página (clic), pasa páginas,
   abre fichas. Nada de `fetch` ni inyección JS.
2. **Espera la carga.** Tras navegar, `wait` 2-3 s o hasta que se estabilice
   (esqueleto → contenido). Si algo no aparece, recarga una vez.
3. **Lee el DOM visible, no el HTML fuente.** Lo que cuenta es lo que un usuario
   ve: precio, año, km, versión, título del anuncio.
4. **Filtra en la página.** Usa los filtros nativos del portal (año, km, precio,
   orden) en lugar de pedir URLs superlargas. Es más fiable y más barato.
5. **Screenshot para verificar (regla Anthropic).** Después de cada paso,
   captura y evalúa si lograste el resultado: "¿Se aplicó el filtro? ¿Cargó la
   ficha?". Si no → reintenta. Solo registra un dato cuando lo has visto en una
   captura.
6. **Clics difíciles → posicional + zoom.** Describe el elemento por posición
   ("el botón azul abajo a la derecha") y usa `zoom` para texto/precios pequeños.
   Si un dropdown no abre, usa teclado (clic + flechas + Enter).
7. **Captcha / bloqueo:** respira. Recarga 1-2 veces con pausa. Si sigue,
   marca la fuente `bloqueada (captcha, N intentos)` y sigue con las demás.
   NUNCA te obsesiones con una fuente bloqueada.
8. **Scroll para cargar más.** En listados infinitos (Wallapop) usa `Page Down`
   hasta que se agote o tengas muestra suficiente (~20-25 anuncios).

---

## 🗺️ Procedimiento por fuente (navegación real)

### 🇩🇪 mobile.de — rey de Alemania

**Cómo navegar (no inyectar JS):**
1. Abre la URL de búsqueda con los filtros mínimos (modelo + país + usado).
2. Usa la columna de filtros de la izquierda haciendo clic: año desde, km máx,
   precio máx, tipo de cambio (Manual/Automático). Aplica.
3. Ordena por precio ascendente (clic en "Preis" o el selector de orden).
4. **Lee la barra de resultados** (número total de coches) — es el dato de muestra.
5. Recorre las primeras 2-3 páginas, lee las tarjetas visibles: título, precio
   (bruto, neto si es comercio), km, año, CV, ciudad, "Fahrzeughalter" si aparece.
6. Abre en pestaña nueva las fichas de los 15-25 mejores candidatos y lee las
   secciones visibles: "Ausstattung" (equipamiento), "Fahrzeugdaten" (datos),
   CO₂, propietarios, historial. Cierra las que no sirvan (siniestro, `NUR AN
   AUTOHÄNDLER`, país raro).
7. **Los precios que ves en la ficha son los que cuentan.** No des por sentado
   el IVA: si el anuncio dice "zzgl. MwSt." o "brutto/netto", anótalo.

**Trucos:**
- Los listados a veces tardan; si ves esqueleto, espera 2-3 s antes de leer.
- `suchen.mobile.de` a veces falla → usa `www.mobile.de/fahrzeuge/search.html?...`.
- El conteo total a veces está en "X Ergebnisse" o "von X Anzeigen".

### 🇩🇪 AutoScout24.de — verificación cruzada

1. Abre `/lst/<marca>/<modelo>?atype=C&fregfrom=<año>&fregto=&powerfrom=&powerto=&powertype=kw&sort=standard`.
2. Lee el número de resultados (contador arriba).
3. Recorre la primera página de tarjetas: precio, km, año, CV, cambio.
4. **Solo contar y validar el hueco. NUNCA fijar precio de referencia** (el skill
   no fija precio en AS24; se usa como cruz de la muestra DE).
5. Si pide consentimiento de cookies, acepta (clic) y sigue.

### 🇩🇪 AutoUncle — ratio de días publicado

1. Abre `/es/coches-segunda-mano/<Marca>/<Modelo>/...`.
2. Lee la tabla/lista de anuncios: precio, año, km, y sobre todo **"publicado
   hace X días"** y el **portal de origen** (de dónde viene el anuncio).
3. AutoUncle agrega anuncios de varios portales → buena joya para días publicado.
4. **Nunca como única fuente DE** — siempre cruza con mobile.de/AS24.

### 🇩🇪 kleinanzeigen.de — chollos de particulares

1. Abre `https://www.kleinanzeigen.de/s-<marca>-<modelo>/k0` con filtros.
2. Lee las tarjetas: precio, km, año, "Privat"/"Gewerblich".
3. Precio negociable (`VB`) suele indicar margen. Anota si es particular.
4. Es pesado con bots: navega lento, una búsqueda por sesión como máximo.

### 🇪🇸 Coches.net — comparable español + tasación

1. Abre la URL de búsqueda con marca/modelo + `segunda-mano`.
2. Espera a que carguen las tarjetas. Lee: precio, año, km, CV, versión,
   `publicationDate` (días publicado) y `priceRankIndicator` si es visible.
3. **Tasación:** en la ficha de un anuncio hay un dato de tasación ("Precio
   tasado" o similar) — léelo cuando necesites el valor de mercado ES.
4. Usa el filtro de orden por precio y la paginación para recorrer.
5. Si la página no muestra datos completos, usa el método degradado: leer lo
   visible (ver `extractores.md` §Trampas). No gastes 3 reintentos en Fase 1.

### 🇪🇸 Wallapop — chollos y rotación

1. Abre `/app/search?keywords=<marca>%20<modelo>%20<version>&category_ids=100`.
2. Acepta cookies si aparece. Lee las tarjetas: año, km, CV, precio, descripción.
3. **Scroll infinito:** baja hasta agotar o tener ~20-25 anuncios.
4. Los anuncios sin año/km completos → márcalos `man`, no los descartes.

### 🇪🇸 Milanuncios — contado vs financiado

1. Abre `/coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>`.
2. Lee cada anuncio: precio, año, km, y si dice "financiado" o "contado".
   (Los financiados inflan el precio de catálogo; descuéntalo mentalmente.)
3. Fecha de publicación y descripción también cuentan.

### 🏁 km77 — PVP y CO₂ (solo Flujo A, verificación)

1. Abre la ficha de datos del modelo: `/coches/<marca>/<modelo>/<año-gama>/.../datos`.
2. Lee: **PVP**, **CO₂ (g/km)**, tipo IEDMT (híbrido/eléctrico/gasolina), etiqueta DGT.
3. Si no encuentras la ficha exacta, busca en km77 la versión más cercana y
   anótalo como "versión aproximada".

---

## 🆚 Cuándo navegar vs cuándo inyectar JS

| Situación | Método |
|---|---|
| **Casi todo** (listados, fichas, conteos, filtros) | 🧭 **Navegar como humano (screenshot + clic)** |
| Dato que no se ve en captura (`publicationDate`, tasación) | Leer equivalente visible o declarar no disponible |
| Captcha persistente | Marcar `bloqueada`, seguir con otra fuente |

**Regla:** intenta SIEMPRE navegación real primero. Si en 2 intentos no puedes
leer lo que necesitas, declara el dato como no disponible (método degradado) o
marca la fuente. NUNCA inyectes JS: no existe en computer use.

---

## 🔢 Presupuesto de tokens en navegación real

Cada acción (clic, scroll, y sobre todo **screenshot ≈ 1.000-1.800 tokens**)
consume presupuesto. Aplica el mismo budget que en SKILL.md §Token budget:

| Fuente | Acciones típicas |
|---|---|
| mobile.de | 1 búsqueda + filtros (3-4 clics) + 2-3 páginas + fichas (15-25) ≈ 25-35 |
| AutoScout24 | 1 búsqueda + 1 página ≈ 5 |
| AutoUncle | 1 búsqueda + 1 página ≈ 5 |
| kleinanzeigen | 1 búsqueda + 1 página ≈ 5 |
| Coches.net | 1 búsqueda + 2-3 páginas + fichas ≈ 10-15 |
| Wallapop | 1 búsqueda + scroll ≈ 5-10 |
| Milanuncios | 1 búsqueda + 2 páginas ≈ 6-8 |
| km77 | 1-2 fichas ≈ 3 |

**Budget por sesión:** mobile.de NUNCA >45 acciones · total Flujo A ≤70 ·
Flujo B ≤50 · Flujo C ≤100 (7 modelos). Avisar al 50% y al 80% (ver SKILL.md).

**Frugalidad:** 1 captura por página en listados; `zoom` solo para datos
pequeños puntuales; verifica con screenshot solo antes de registrar un dato
importante (precio de candidato, CO₂, conteo total).
