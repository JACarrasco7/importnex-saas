# PLAN — Fotos correctas + Marketing completo en el ZIP (handoff 2026-09-03)

> **Sesión nueva: empieza leyendo este archivo.** No hace falta re-investigar nada;
> todo el contexto y las decisiones están aquí.

## 🎯 Petición del usuario (verbatim, 03-sep-2026)

> "al generar la skill a veces no guarda las imágenes correctamente, esto debe ser
> obligatorio hacer y cerciorarse que esté bien. Y genera lo de marketing: cuando
> genera el ZIP también debe generar todo para el módulo de marketing. ¿Entiendes?
> En Laravel hay módulo de marketing pero también puede venir desde Claude del ZIP,
> para redes sociales y para anuncios web."

**Traducción a requisitos:**
1. **R1 — Fotos OBLIGATORIAS y correctas**: al generar el ZIP (Flujo A), las fotos
   deben descargarse SIEMPRE del anuncio real (URLs del portal, no capturas de
   pantalla) y validarse (Content-Type image/*, tamaño razonable). Si falla alguna,
   avisar — NUNCA sustituir por capturas.
2. **R2 — Marketing en el ZIP OBLIGATORIO**: el ZIP debe incluir SIEMPRE
   `contenido/redes-sociales.txt` y `contenido/anuncio-portales.txt` (esqueletos
   `[BLOQUE]` según `contrato.md`).
3. **R3 — Laravel importa el marketing**: el módulo Laravel de marketing
   (`CarMarketingContent`) debe poder poblararse DESDE el ZIP (no solo generar con
   IA desde el panel). Ambos orígenes coexisten: Claude (ZIP) + IA (panel).

## 🔍 Estado actual (ya investigado, NO re-investigar)

### Lo que YA existe (verificado con grep/read):

| Pieza | Estado | Ruta |
|---|---|---|
| `empaquetar.py` | ❌ **NO EXISTE** (la skill lo referencia 17 veces pero no está creado) | Debería estar en `.claude/skills/importacion-vehiculos/scripts/` |
| Definición bloques marketing | ✅ Definida | `.claude/skills/importacion-vehiculos/03-informes/contrato.md` líneas 558-563 |
| Ingestor ZIP Laravel | ✅ Funciona | `app/Services/ValuationPackageIngestor.php` |
| — guarda `contenido/*.txt` | ✅ Cualquier .txt → `cars/{id}/contenido/` (persiste redes-sociales.txt y anuncio-portales.txt automáticamente) | `attachContent()` |
| Modelo marketing | ✅ Existe | `app/Models/CarMarketingContent.php` — canales: `milanuncios, coches_net, wallapop, tiktok, instagram, facebook` · campos: `title, description, hashtags[], photo_tips[], status(draft/published/archived)` |
| Servicio IA marketing | ✅ Existe | `app/Services/CarMarketingService.php` — genera con IA por canal, NO lee el ZIP |
| Controller marketing | ✅ Existe | `app/Http/Controllers/CarMarketingController.php` — show/generate/save/publish |
| Rutas marketing | ✅ Existen | `routes/web.php` líneas ~249-253 (`cars.marketing*`) |
| Modalidad M1/M2/M3 | Ya implementada en skill | — |

### Gap exacto (lo que falta):

1. **`empaquetar.py` no existe** → Claude arma el ZIP "a mano" cada vez → a veces
   olvida fotos o marketing. Necesita script reproducible con validación dura.
2. **Nadie parsea los .txt de marketing → `CarMarketingContent`** → aunque el ZIP
   traiga `redes-sociales.txt`, Laravel solo lo guarda como archivo plano; el
   módulo de marketing del panel no lo muestra.

### Contrato de bloques (sacado de contrato.md, NO re-leer):

```
redes-sociales.txt:    [GANCHO] [POST_LARGO] [POST_CORTO] [STORIES] [HASHTAGS] [PIE_FOTO]
anuncio-portales.txt:  [TITULO] [DESCRIPCION] [FICHA_RAPIDA] [QUE_INCLUYE] [AVISO_LEGAL]
```

Formato esqueleto (parser PHP `App\Support\Esqueleto` ya existe):
- Línea `[NOMBRE] contenido` = bloque; repetido = lista.
- Campos múltiples separados ` | `. Énfasis `**negrita**`. `#` = comentario.

### Cómo mapear ZIP → CarMarketingContent (diseño decidido):

| Origen | Canal CarMarketingContent | Campos |
|---|---|---|
| `redes-sociales.txt` `[GANCHO]`+`[POST_LARGO]`+`[HASHTAGS]` | `instagram` | title=GANCHO, description=POST_LARGO, hashtags |
| `redes-sociales.txt` `[POST_CORTO]`+`[STORIES]` | `tiktok` | title=GANCHO, description=STORIES, hashtags |
| `anuncio-portales.txt` `[TITULO]`+`[DESCRIPCION]` | `milanuncios`, `coches_net`, `wallapop`, `facebook` (mismo texto base) | title, description |
| photo_tips | `[PIE_FOTO]` → array de 1 | |
| status | `draft` siempre al importar (usuario publica desde panel) | |

⚠️ `CarMarketingContent::CHANNELS` NO tiene canal genérico "redes" — mapear a los
6 existentes. Actualizar con `updateOrCreate` para idempotencia (reimport no duplica).

## 📋 Tareas (en orden)

### T1 — Crear `.claude/skills/importacion-vehiculos/scripts/empaquetar.py`
- Input: `export/flujo-a-<coche_id>.json` · Output: `paquetes/<coche_id>.zip`
- Descarga fotos de `vehiculo.fotos[]` con UA navegador + Referer del anuncio.
- Valida cada foto: HTTP 200, `Content-Type: image/*`, tamaño >1KB, dedup por hash.
- Foto fallida → warning, NUNCA captura. 0 fotos OK → modo strict falla.
- Genera SIEMPRE: `informe.json`, `manifest.json` (paquete_version 2),
  `contenido/ficha-publicitaria.txt`, `contenido/informe-interno.txt`,
  `contenido/redes-sociales.txt`, `contenido/anuncio-portales.txt`,
  `contenido/dossier-cliente.txt` (solo si veredicto Comprar*).
- Fallbacks inteligentes: si falta `publicidad.claim` usar titular, etc.
- Flag `--strict` para validación dura (CI).
- NOTA: el usuario CANCELÓ una versión previa de este archivo el 03-sep —
  revisar si dejó esbozo o crear de cero. La versión cancelada estaba completa;
  se puede recuperar del transcript si hace falta.

### T2 — Laravel: importar marketing del ZIP → CarMarketingContent
- En `ValuationPackageIngestor::ingest()`, tras `attachContent()`:
  si existe `contenido/redes-sociales.txt` o `contenido/anuncio-portales.txt`,
  parsearlos con `App\Support\Esqueleto::desde()` y hacer
  `CarMarketingContent::updateOrCreate(['car_id','channel'], [...])` con status `draft`.
- Devolver contador nuevo en el array resultado (`'marketing' => N`).
- Respetar re-import: updateOrCreate es idempotente.

### T3 — Test feature PHPUnit
- `tests/Feature/ValuationPackageMarketingTest.php`:
  - ZIP con los 2 .txt de marketing → crea CarMarketingContent en canales esperados.
  - Re-import mismo ZIP → no duplica (mismo count).
  - ZIP sin marketing → 0 filas, sin error.
  - Fotos: ZIP con foto fake binario → se adjunta; con archivo no-imagen → warning.

### T4 — Documentar reglas duras en la skill
- `SKILL.md` §ZIP: añadir que fotos correctas + marketing son OBLIGATORIOS y que
  `empaquetar.py` es la vía (prohibido armar ZIP a mano).
- `03-informes/contrato.md`: nota de que Laravel importa los .txt de marketing a
  `CarMarketingContent` (status draft).
- `06-reglas/anti_patrones.md`: nuevo A22 — "ZIP sin fotos reales validadas o sin
  marketing = entrega INVÁLIDA".

### T5 — Validación end-to-end
- Generar ZIP de prueba con el script (JSON ejemplo del contrato).
- Subir por panel (`POST /cars/import-valuation`) en local.
- Ver: fotos en galería, .txt en `cars/{id}/contenido/`, filas en
  `car_marketing_contents`, contenido visible en `/cars/{id}/marketing`.
- `vendor/bin/pint --dirty` + `php artisan test --compact` filtrado.

## 🔒 Reglas del proyecto que aplican

- Tests PHPUnit obligatorios por cambio (`php artisan make:test --phpunit`).
- Pint antes de cerrar: `vendor/bin/pint --dirty --format agent`.
- NUNCA `npm run build` (lo lanza el usuario).
- Leer `.ai/rules/index.md` antes de tocar código (regla Boost).
- Migración nueva NO necesaria (tabla `car_marketing_contents` ya existe).

## 📍 Punto de partida exacto

Empezar por **T1** (crear empaquetar.py). La sesión anterior tenía una versión
completa escrita que el usuario canceló para reorganizar la sesión — el diseño
(estructura de funciones: `download_photo`, `generar_*`, `build_zip`, `main`) era
sólido y validado contra el contrato. Continuar desde ahí, no rediseñar.
